import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../i18n/strings.dart';
import '../state/app_state.dart';
import 'dashboard_screen.dart';
import 'eligibility_screen.dart';
import 'sponsors_screen.dart';
import 'workers_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final t = S.of(context);
    final app = context.watch<AppState>();

    const pages = [
      DashboardScreen(),
      EligibilityScreen(),
      SponsorsScreen(),
      WorkersScreen(),
    ];
    final titles = [t.dashboard, t.eligibility, t.sponsors, t.workers];

    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_index]),
        actions: [
          IconButton(
            tooltip: t.language,
            onPressed: app.toggleLocale,
            icon: const Icon(Icons.language),
          ),
          IconButton(
            tooltip: t.signOut,
            onPressed: () => app.logout(),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: [
          NavigationDestination(icon: const Icon(Icons.dashboard_outlined), label: t.dashboard),
          NavigationDestination(icon: const Icon(Icons.verified_user_outlined), label: t.eligibility),
          NavigationDestination(icon: const Icon(Icons.people_outline), label: t.sponsors),
          NavigationDestination(icon: const Icon(Icons.badge_outlined), label: t.workers),
        ],
      ),
    );
  }
}
