import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../i18n/strings.dart';
import '../state/app_state.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = context.read<AppState>().api.get('/dashboard');
  }

  @override
  Widget build(BuildContext context) {
    final t = S.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snap) {
        if (!snap.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final d = snap.data!;
        final workers = (d['workers']?['total'] ?? 0).toString();
        final contracts = (d['contracts']?['active'] ?? 0).toString();
        final visa = (d['visa_pipeline']?['open'] ?? 0).toString();
        final outstanding =
            (((d['billing']?['outstanding_fils'] ?? 0) as num) / 1000).toStringAsFixed(3);

        return GridView.count(
          padding: const EdgeInsets.all(16),
          crossAxisCount: 2,
          childAspectRatio: 1.5,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          children: [
            _kpi(t.workersCount, workers),
            _kpi(t.activeContracts, contracts),
            _kpi(t.openVisa, visa),
            _kpi(t.outstanding, outstanding),
          ],
        );
      },
    );
  }

  Widget _kpi(String label, String value) => Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(label, style: const TextStyle(color: Colors.grey)),
              const SizedBox(height: 8),
              Text(value, style: const TextStyle(fontSize: 30, fontWeight: FontWeight.bold)),
            ],
          ),
        ),
      );
}
