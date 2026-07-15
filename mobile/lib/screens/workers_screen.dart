import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../i18n/strings.dart';
import '../state/app_state.dart';
import '../widgets/record_list.dart';

class WorkersScreen extends StatelessWidget {
  const WorkersScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final t = S.of(context);
    return RecordList(
      fetch: () => context.read<AppState>().api.get('/workers'),
      emptyLabel: t.empty,
      itemBuilder: (row) => ListTile(
        leading: const CircleAvatar(child: Icon(Icons.badge)),
        title: Text((row['name_en'] ?? row['name_ar'] ?? '').toString()),
        subtitle: Text('${t.nationality}: ${row['nationality'] ?? '-'}'),
        trailing: Chip(label: Text((row['status'] ?? '').toString())),
      ),
    );
  }
}
