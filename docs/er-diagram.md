# ER図

```mermaid
erDiagram
    projects ||--o{ test_cases : has
    projects ||--o{ test_runs : has
    projects ||--o{ defects : has
    test_runs ||--o{ test_results : has
    test_cases ||--o{ test_results : "is executed as"
    test_results ||--o{ defects : "may raise"

    projects {
        bigint id PK
        string name "100文字まで"
        text description
    }
    test_cases {
        bigint id PK
        bigint project_id FK
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
    defects {
        bigint id PK
        bigint project_id FK
        bigint test_result_id FK "任意"
        string title
        string severity "critical / major / minor / trivial"
        string status "open / in_progress / resolved / closed / rejected"
    }
```

- `test_results` は `(test_run_id, test_case_id)` に一意制約をかけ、1回のテスト実行で同じテストケースの結果が重複しないようにしています。
- プロジェクトを削除すると、配下のテストケース・テスト実行・不具合も削除されます（`cascadeOnDelete`）。
- テスト結果を削除しても不具合は残し、紐づけだけを外します（`nullOnDelete`）。
