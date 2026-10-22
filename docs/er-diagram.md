# ER図

```mermaid
erDiagram
    projects ||--o{ sections : has
    sections ||--o{ sections : "parent of"
    sections ||--o{ test_cases : groups
    projects ||--o{ test_cases : has
    projects ||--o{ test_runs : has
    projects ||--o{ defects : has
    test_runs ||--o{ test_results : has
    test_cases ||--o{ test_results : "is executed as"
    test_results ||--o{ defects : "may raise"
    test_results ||--o{ test_result_histories : "is recorded as"

    projects {
        bigint id PK
        string name "100文字まで"
        text description
    }
    sections {
        bigint id PK
        bigint project_id FK
        bigint parent_id FK "上位のセクション（シートはnull）"
        string name
        tinyint depth "1:シート 2:大項目 3:中項目 4:小項目"
        int sort_order
    }
    test_cases {
        bigint id PK
        bigint project_id FK
        bigint section_id FK "任意（未分類も可）"
        string title
        text preconditions
        text steps
        text expected_result
        string priority "high / medium / low"
        boolean is_active
    }
    test_runs {
        bigint id PK
        bigint project_id FK
        string name
        string environment
    }
    test_results {
        bigint id PK
        bigint test_run_id FK
        bigint test_case_id FK
        string status "untested / passed / failed / blocked / skipped"
        text note "failed・blockedのとき必須"
        timestamp executed_at
    }
    test_result_histories {
        bigint id PK
        bigint test_result_id FK
        string status
        text note
        timestamp executed_at
    }
    defects {
        bigint id PK
        bigint project_id FK
        bigint test_result_id FK "任意"
        string title
        string severity "critical / major / minor / trivial"
        string status "open / in_progress / resolved / closed / rejected"
    }
```

- `sections` は自分自身を親に持つ構造で、「シート → 大項目 → 中項目 → 小項目」の4段まで作れます。
- `test_results` は最新の結果だけを持ち、記録のたびに `test_result_histories` へ1行追加します（NG → 修正 → 再テストで OK、という経緯を残すため）。
- セクションを削除すると下位のセクションも削除され、所属していたテストケースは「未分類」になります（`nullOnDelete`）。
- `test_results` は `(test_run_id, test_case_id)` に一意制約をかけ、1回のテスト実行で同じテストケースの結果が重複しないようにしています。
- プロジェクトを削除すると、配下のテストケース・テスト実行・不具合も削除されます（`cascadeOnDelete`）。
- テスト結果を削除しても不具合は残し、紐づけだけを外します（`nullOnDelete`）。
