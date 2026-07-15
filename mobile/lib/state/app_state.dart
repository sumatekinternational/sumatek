import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api/api_client.dart';
import '../config.dart';

/// Holds auth token, current user and locale; persisted across launches.
class AppState extends ChangeNotifier {
  AppState(this._prefs) {
    _token = _prefs.getString('token');
    _locale = _prefs.getString('locale') ?? 'ar';
    api = ApiClient(baseUrl: apiBaseUrl, token: _token, locale: _locale);
  }

  final SharedPreferences _prefs;
  late final ApiClient api;
  String? _token;
  String _locale = 'ar';
  Map<String, dynamic>? user;

  bool get isAuthenticated => _token != null;
  String get locale => _locale;
  bool get isRtl => _locale == 'ar';

  List<String> get permissions =>
      (user?['permissions'] as List?)?.map((e) => e.toString()).toList() ?? const [];

  bool can(String permission) => permissions.contains(permission);

  Future<void> toggleLocale() => setLocale(_locale == 'ar' ? 'en' : 'ar');

  Future<void> setLocale(String value) async {
    _locale = value;
    api.locale = value;
    await _prefs.setString('locale', value);
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    final data = await api.post('/auth/login', {
      'email': email,
      'password': password,
      'device_name': 'mobile',
    });
    _token = data['token'] as String?;
    user = data['user'] as Map<String, dynamic>?;
    api.token = _token;
    await _prefs.setString('token', _token ?? '');
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await api.post('/auth/logout', {});
    } catch (_) {
      // clear locally regardless
    }
    _token = null;
    user = null;
    api.token = null;
    await _prefs.remove('token');
    notifyListeners();
  }
}
