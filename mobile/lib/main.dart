import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'state/app_state.dart';
import 'theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final prefs = await SharedPreferences.getInstance();
  runApp(
    ChangeNotifierProvider(
      create: (_) => AppState(prefs),
      child: const ManpowerApp(),
    ),
  );
}

class ManpowerApp extends StatelessWidget {
  const ManpowerApp({super.key});

  @override
  Widget build(BuildContext context) {
    final app = context.watch<AppState>();
    return MaterialApp(
      title: 'Manpower SaaS',
      debugShowCheckedModeBanner: false,
      theme: buildTheme(),
      // Arabic locale drives right-to-left layout automatically (§7).
      locale: Locale(app.locale),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: app.isAuthenticated ? const HomeScreen() : const LoginScreen(),
    );
  }
}
