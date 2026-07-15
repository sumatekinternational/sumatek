import 'package:flutter/material.dart';

/// Fetches a paginated `{ "data": [...] }` endpoint and renders each row.
class RecordList extends StatefulWidget {
  const RecordList({
    super.key,
    required this.fetch,
    required this.itemBuilder,
    required this.emptyLabel,
  });

  final Future<Map<String, dynamic>> Function() fetch;
  final Widget Function(Map<String, dynamic> row) itemBuilder;
  final String emptyLabel;

  @override
  State<RecordList> createState() => _RecordListState();
}

class _RecordListState extends State<RecordList> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.fetch();
  }

  Future<void> _refresh() async {
    final f = widget.fetch();
    setState(() => _future = f);
    await f;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snap) {
        if (snap.hasError) {
          return Center(child: Text(snap.error.toString()));
        }
        if (!snap.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final rows = (snap.data!['data'] as List?) ?? const [];
        if (rows.isEmpty) {
          return Center(child: Text(widget.emptyLabel));
        }
        return RefreshIndicator(
          onRefresh: _refresh,
          child: ListView.separated(
            itemCount: rows.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, i) =>
                widget.itemBuilder(rows[i] as Map<String, dynamic>),
          ),
        );
      },
    );
  }
}
