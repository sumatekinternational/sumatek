import 'package:flutter/widgets.dart';
import 'package:provider/provider.dart';

import '../state/app_state.dart';

/// Minimal bilingual string table. `S.of(context)` picks Arabic or English from
/// the current AppState locale.
class S {
  const S(this.ar);

  final bool ar;

  static S of(BuildContext context) => S(context.watch<AppState>().isRtl);

  String get appName => ar ? 'منصة الاستقدام' : 'Manpower SaaS';
  String get subtitle => ar ? 'منصة العمالة المنزلية في الكويت' : 'Kuwait Domestic Labour Platform';

  String get email => ar ? 'البريد الإلكتروني' : 'Email';
  String get password => ar ? 'كلمة المرور' : 'Password';
  String get signIn => ar ? 'تسجيل الدخول' : 'Sign in';
  String get signOut => ar ? 'تسجيل الخروج' : 'Sign out';
  String get loading => ar ? 'جارٍ التحميل…' : 'Loading…';

  String get dashboard => ar ? 'لوحة التحكم' : 'Dashboard';
  String get eligibility => ar ? 'فحص الأهلية' : 'Eligibility';
  String get sponsors => ar ? 'الكفلاء' : 'Sponsors';
  String get workers => ar ? 'العمالة' : 'Workers';

  String get civilId => ar ? 'الرقم المدني' : 'Civil ID';
  String get check => ar ? 'فحص' : 'Check';
  String get scanId => ar ? 'مسح البطاقة' : 'Scan ID';
  String get enterCivilId => ar ? 'أدخل الرقم المدني للكفيل' : "Enter the sponsor's Civil ID";

  String get statusClear => ar ? 'لا يوجد حظر' : 'CLEAR';
  String get statusCaution => ar ? 'تنبيه' : 'CAUTION';
  String get statusBlocked => ar ? 'محظور' : 'BLOCKED';

  String get workersCount => ar ? 'العمالة' : 'Workers';
  String get activeContracts => ar ? 'العقود النشطة' : 'Active contracts';
  String get openVisa => ar ? 'معاملات التأشيرات' : 'Open visa cases';
  String get outstanding => ar ? 'مستحقات (د.ك)' : 'Outstanding (KWD)';

  String get nationality => ar ? 'الجنسية' : 'Nationality';
  String get status => ar ? 'الحالة' : 'Status';
  String get empty => ar ? 'لا توجد بيانات' : 'No records found';
  String get language => ar ? 'English' : 'العربية';
  String get scanTodo =>
      ar ? 'مسح البطاقة بالكاميرا قيد التطوير' : 'Camera ID scan is coming soon';
}
