import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../i18n/strings.dart';
import '../state/app_state.dart';
import '../theme.dart';

/// Flagship: scan/enter a Civil ID -> instant cross-agency block result (§4).
class EligibilityScreen extends StatefulWidget {
  const EligibilityScreen({super.key});

  @override
  State<EligibilityScreen> createState() => _EligibilityScreenState();
}

class _EligibilityScreenState extends State<EligibilityScreen> {
  final _civilId = TextEditingController();
  bool _loading = false;
  String? _error;
  Map<String, dynamic>? _result;

  @override
  void dispose() {
    _civilId.dispose();
    super.dispose();
  }

  Future<void> _check() async {
    if (_civilId.text.trim().isEmpty) return;
    setState(() {
      _loading = true;
      _error = null;
      _result = null;
    });
    try {
      final data = await context
          .read<AppState>()
          .api
          .post('/eligibility/check', {'civil_id': _civilId.text.trim()});
      setState(() => _result = data);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _scan() {
    // Integration point (§5): open the camera, OCR the passport MRZ / read the
    // Civil ID card, then populate the field. Wired to the identity endpoint.
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(S.of(context).scanTodo)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final t = S.of(context);
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  controller: _civilId,
                  keyboardType: TextInputType.number,
                  decoration: InputDecoration(
                    labelText: t.civilId,
                    hintText: t.enterCivilId,
                  ),
                  onSubmitted: (_) => _check(),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: _loading ? null : _check,
                        icon: const Icon(Icons.search),
                        label: Text(_loading ? t.loading : t.check),
                      ),
                    ),
                    const SizedBox(width: 12),
                    OutlinedButton.icon(
                      onPressed: _scan,
                      icon: const Icon(Icons.document_scanner_outlined),
                      label: Text(t.scanId),
                    ),
                  ],
                ),
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: Colors.red)),
                ],
              ],
            ),
          ),
        ),
        if (_result != null) _resultCard(t, _result!),
      ],
    );
  }

  Widget _resultCard(S t, Map<String, dynamic> r) {
    final status = (r['status'] ?? 'clear').toString();
    final color = statusColor(status);
    final label = switch (status) {
      'blocked' => t.statusBlocked,
      'caution' => t.statusCaution,
      _ => t.statusClear,
    };

    return Card(
      color: color.withValues(alpha: 0.08),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: color, width: 2),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(label,
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: color)),
                Text((r['civil_id_masked'] ?? '').toString(),
                    style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1)),
              ],
            ),
            const SizedBox(height: 8),
            Text((r['prompt'] ?? '').toString(),
                style: TextStyle(color: color, fontWeight: FontWeight.w500)),
            if ((r['disclaimer'] ?? '').toString().isNotEmpty) ...[
              const SizedBox(height: 12),
              Text((r['disclaimer']).toString(),
                  style: const TextStyle(fontSize: 11, color: Colors.black54)),
            ],
          ],
        ),
      ),
    );
  }
}
