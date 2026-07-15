import 'package:flutter/material.dart';

const brandColor = Color(0xFF1D4ED8);
const brandDark = Color(0xFF1E3A8A);

ThemeData buildTheme() {
  final scheme = ColorScheme.fromSeed(seedColor: brandColor);
  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: const Color(0xFFF1F5F9),
    inputDecorationTheme: const InputDecorationTheme(
      border: OutlineInputBorder(),
      filled: true,
      fillColor: Colors.white,
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(50),
        backgroundColor: brandColor,
      ),
    ),
  );
}

Color statusColor(String status) {
  switch (status) {
    case 'blocked':
      return const Color(0xFFDC2626);
    case 'caution':
      return const Color(0xFFD97706);
    default:
      return const Color(0xFF059669);
  }
}
