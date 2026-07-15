import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../i18n/strings.dart';
import '../state/app_state.dart';
import '../widgets/record_list.dart';

class SponsorsScreen extends StatelessWidget {
  const SponsorsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = S.of(context);
    return RecordList(
      fetch: () => context.read<AppState>().api.get('/sponsors'),
      emptyLabel: t.empty,
      itemBuilder: (row) => ListTile(
        leading: const CircleAvatar(child: Icon(Icons.person)),
        title: Text((row['name_ar'] ?? row['name_en'] ?? '').toString()),
        subtitle: Text('${t.nationality}: ${row['nationality'] ?? '-'}'),
        trailing: Text((row['phone'] ?? '').toString()),
      ),
    );
  }
}
