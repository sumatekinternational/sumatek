import 'dart:convert';

import 'package:http/http.dart' as http;

/// Thin HTTP client for the v1 API. Attaches the Sanctum bearer token and the
/// active locale (so server messages come back translated).
class ApiClient {
  ApiClient({required this.baseUrl, this.token, this.locale = 'ar'});

  final String baseUrl;
  String? token;
  String locale;

  Map<String, String> _headers({bool json = true}) => {
        'Accept': 'application/json',
        'Accept-Language': locale,
        if (json) 'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1$path'),
      headers: _headers(),
      body: jsonEncode(body),
    );
    return _decode(res);
  }

  Future<Map<String, dynamic>> get(String path) async {
    final res = await http.get(Uri.parse('$baseUrl/api/v1$path'), headers: _headers(json: false));
    return _decode(res);
  }

  Map<String, dynamic> _decode(http.Response res) {
    final body = res.body.isEmpty ? <String, dynamic>{} : jsonDecode(res.body);
    final map = body is Map<String, dynamic> ? body : <String, dynamic>{'data': body};
    if (res.statusCode >= 400) {
      throw ApiException(
        (map['message'] ?? 'Request failed (${res.statusCode})').toString(),
        res.statusCode,
      );
    }
    return map;
  }
}

class ApiException implements Exception {
  ApiException(this.message, this.statusCode);

  final String message;
  final int statusCode;

  @override
  String toString() => message;
}
