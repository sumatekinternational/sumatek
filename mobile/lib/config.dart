/// Base URL of the Laravel API.
///
/// Override at build/run time:  flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000
///
///  - Android emulator: http://10.0.2.2:8000   (10.0.2.2 = your PC's localhost)
///  - iOS simulator:    http://127.0.0.1:8000
///  - Real phone:       http://<your-PC-LAN-IP>:8000  (e.g. http://192.168.1.186:8000)
const String apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://10.0.2.2:8000',
);
