<?php

namespace Tests\Unit;

use App\Services\Identity\MrzParser;
use PHPUnit\Framework\TestCase;

class MrzParserTest extends TestCase
{
    private MrzParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MrzParser;
    }

    /** ICAO 9303 worked example: the 7-3-1 check digit of "L898902C3" is 6. */
    public function test_check_digit_matches_icao_example(): void
    {
        $this->assertSame('6', $this->parser->checkDigit('L898902C3'));
    }

    public function test_parses_td3_passport_names_and_fields(): void
    {
        $line1 = 'P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<';
        $line2 = 'L898902C36UTO7408122F1204159ZE184226B<<<<<10';

        $data = $this->parser->parse($line1."\n".$line2);

        $this->assertSame('passport', $data->documentType);
        $this->assertSame('L898902C3', $data->number);
        $this->assertSame('ANNA MARIA ERIKSSON', $data->nameEn);
        $this->assertSame('F', $data->sex);
        $this->assertSame('UTO', $data->nationality);
        $this->assertSame('1974-08-12', $data->dateOfBirth);
        $this->assertArrayHasKey('mrz_valid', $data->meta);
    }

    public function test_rejects_unsupported_line_count(): void
    {
        $this->expectExceptionMessage('Unsupported MRZ format');
        $this->parser->parse('SINGLE LINE ONLY');
    }
}
