<?php

namespace App\Services\Import;

use App\Models\DataImport;
use App\Models\Sponsor;
use App\Models\Worker;
use App\Services\Audit\AuditLogger;
use App\Support\Pii;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Legacy migration tool (§6.6): maps an uploaded CSV onto sponsor/worker
 * records with validation, dedupe (Civil ID / passport), dry-run preview,
 * error report and full rollback.
 */
class ImportService
{
    private const ERROR_CAP = 200;

    /**
     * Per-type field schema: field => [required, is_dedupe_key].
     */
    public const SCHEMA = [
        'sponsors' => [
            'civil_id' => [true, true],
            'name_ar' => [true, false],
            'name_en' => [false, false],
            'date_of_birth' => [false, false],
            'sex' => [false, false],
            'nationality' => [false, false],
            'phone' => [false, false],
            'email' => [false, false],
            'address_ar' => [false, false],
            'address_en' => [false, false],
        ],
        'workers' => [
            'passport_no' => [true, true],
            'name_en' => [true, false],
            'nationality' => [true, false],
            'name_ar' => [false, false],
            'date_of_birth' => [false, false],
            'sex' => [false, false],
            'experience_years' => [false, false],
            'medical_status' => [false, false],
            'source_agency' => [false, false],
        ],
    ];

    public function __construct(protected AuditLogger $audit) {}

    /** Header columns for the downloadable import template. */
    public static function templateColumns(string $type): array
    {
        return array_keys(self::SCHEMA[$type] ?? []);
    }

    /** Dry-run: validate + dedupe-check, write nothing. */
    public function preview(DataImport $import): DataImport
    {
        [$stats, $errors] = $this->process($import, commit: false);

        $import->update([
            'status' => 'previewed',
            'stats' => $stats,
            'errors' => array_slice($errors, 0, self::ERROR_CAP),
        ]);

        return $import;
    }

    /** Commit the import, tagging created rows for rollback. */
    public function run(DataImport $import): DataImport
    {
        $createdIds = [];

        $result = DB::transaction(function () use ($import, &$createdIds) {
            return $this->process($import, commit: true, createdIds: $createdIds);
        });

        [$stats, $errors] = $result;

        $import->update([
            'status' => 'completed',
            'stats' => $stats,
            'errors' => array_slice($errors, 0, self::ERROR_CAP),
            'created_ids' => $createdIds,
            'completed_at' => now(),
        ]);

        $this->audit->log('import.completed', $import, $stats);

        return $import;
    }

    /** Delete every record this import created. */
    public function rollback(DataImport $import): DataImport
    {
        abort_unless($import->status === 'completed', 422, 'Only a completed import can be rolled back.');

        $model = $import->type === 'sponsors' ? Sponsor::class : Worker::class;

        DB::transaction(function () use ($model, $import) {
            $model::where('data_import_id', $import->id)->forceDelete();
            $import->update(['status' => 'rolled_back']);
        });

        $this->audit->log('import.rolled_back', $import, ['type' => $import->type]);

        return $import;
    }

    /**
     * Core pass over the file. Returns [stats, errors]; when committing, fills
     * $createdIds by reference.
     *
     * @return array{0: array, 1: array}
     */
    protected function process(DataImport $import, bool $commit, array &$createdIds = []): array
    {
        [$headers, $rows] = $this->readCsv($import->file_path);
        $mapping = $this->resolveMapping($import, $headers);
        $schema = self::SCHEMA[$import->type];
        $dedupeKey = $this->dedupeField($import->type);

        $stats = ['total' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $errors = [];

        foreach ($rows as $i => $row) {
            $stats['total']++;
            $line = $i + 2; // +1 header, +1 to 1-base

            $attributes = $this->mapRow($row, $headers, $mapping, $schema);
            $rowErrors = $this->validate($attributes, $schema);

            if ($rowErrors) {
                $stats['errors']++;
                $errors[] = ['line' => $line, 'errors' => $rowErrors];

                continue;
            }

            $outcome = $this->upsert($import, $attributes, $dedupeKey, $commit, $createdIds);
            $stats[$outcome]++;
        }

        return [$stats, $errors];
    }

    protected function upsert(DataImport $import, array $attributes, string $dedupeKey, bool $commit, array &$createdIds): string
    {
        $model = $import->type === 'sponsors' ? Sponsor::class : Worker::class;
        $hashColumn = $dedupeKey.'_hash';
        $hash = Pii::hash($attributes[$dedupeKey]);

        $existing = $model::where($hashColumn, $hash)->first();

        if ($existing) {
            if ($import->dedupe_strategy !== 'update') {
                return 'skipped';
            }
            if ($commit) {
                $existing->fill($attributes)->save();
            }

            return 'updated';
        }

        if ($commit) {
            $attributes['data_import_id'] = $import->id;
            $record = $model::create($attributes);
            $createdIds[] = $record->id;
        }

        return 'created';
    }

    protected function validate(array $attributes, array $schema): array
    {
        $errors = [];

        foreach ($schema as $field => [$required]) {
            if ($required && blank($attributes[$field] ?? null)) {
                $errors[$field] = 'required';
            }
        }

        if (! empty($attributes['sex']) && ! in_array($attributes['sex'], ['M', 'F'], true)) {
            $errors['sex'] = 'must be M or F';
        }

        if (! empty($attributes['date_of_birth']) && strtotime($attributes['date_of_birth']) === false) {
            $errors['date_of_birth'] = 'invalid date';
        }

        return $errors;
    }

    protected function mapRow(array $row, array $headers, array $mapping, array $schema): array
    {
        $byHeader = array_combine($headers, array_pad($row, count($headers), null));
        $attributes = [];

        foreach (array_keys($schema) as $field) {
            $column = $mapping[$field] ?? null;
            $value = $column !== null ? trim((string) ($byHeader[$column] ?? '')) : '';
            $attributes[$field] = $value === '' ? null : $value;
        }

        return $attributes;
    }

    /** Default mapping is identity for any column whose header equals a field. */
    protected function resolveMapping(DataImport $import, array $headers): array
    {
        if (! empty($import->mapping)) {
            return $import->mapping;
        }

        $mapping = [];
        foreach (array_keys(self::SCHEMA[$import->type]) as $field) {
            if (in_array($field, $headers, true)) {
                $mapping[$field] = $field;
            }
        }

        return $mapping;
    }

    protected function dedupeField(string $type): string
    {
        foreach (self::SCHEMA[$type] as $field => [$required, $isKey]) {
            if ($isKey) {
                return $field;
            }
        }

        return array_key_first(self::SCHEMA[$type]);
    }

    /** @return array{0: array<int,string>, 1: array<int,array>} */
    protected function readCsv(string $path): array
    {
        $contents = Storage::disk('local')->get($path);
        $contents = Str::of($contents)->ltrim("\xEF\xBB\xBF")->toString(); // strip UTF-8 BOM

        $lines = preg_split('/\r\n|\r|\n/', rtrim($contents));
        $headers = str_getcsv((string) array_shift($lines));
        $headers = array_map('trim', $headers);

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line);
        }

        return [$headers, $rows];
    }
}
