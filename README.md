# Test Case Manager API

[![tests](https://github.com/qedhourse580/test-case-manager-api/actions/workflows/tests.yml/badge.svg)](https://github.com/qedhourse580/test-case-manager-api/actions/workflows/tests.yml)

テストケース・テスト実行・不具合を管理する REST API です（Laravel 13 / PHP 8.3+）。

QAエンジニアとして日々使ってきたテスト管理ツールの仕組みを、開発者の立場から設計・実装しました。
「テストしやすく、仕様がコードとテストで説明されているAPI」を目標にしています。

## 主な機能

| 機能 | 内容 |
|---|---|
| プロジェクト管理 | プロジェクトの作成・一覧・更新・削除 |
| テストケース管理 | 手順・期待結果・優先度の登録、優先度・有効フラグでの絞り込み |
| テスト実行 | 実行を作成すると、有効なテストケースの「未実施」結果を自動で用意 |
| 結果の記録 | passed / failed / blocked / skipped を記録。failed・blocked は理由の入力を必須に |
| 進捗の集計 | 進捗率・合格率をAPIで取得 |
| 不具合管理 | テスト結果と紐づけて起票、重要度・ステータスでの絞り込み、集計 |
| 状態遷移の制御 | 不具合ステータスは、許可された遷移以外を 422 で拒否 |

## 技術スタック
- PHP 8.3 / 8.4、Laravel 13
- SQLite（開発・テスト）／ MySQL にも対応可能
- PHPUnit 12（Unit / Feature テスト 58件）
- GitHub Actions（PHP 8.3・8.4 でテストを自動実行）

## 設計のポイント

### 1. 仕様をEnumに集約
ステータス・優先度・重要度は PHP の Enum で定義し、文字列の書き間違いを防いでいます。
不具合の状態遷移ルールも `DefectStatus::allowedTransitions()` の1か所にまとめています。

```
open ──▶ in_progress ──▶ resolved ──▶ closed
 │  ▲         │              │
 │  └─────────┘ (差し戻し)   └──▶ open (再オープン)
 ▼
rejected ──▶ open (再オープン)
```

`closed` は終端で、再オープンしたい場合は新しい不具合として起票する運用を想定しています。

### 2. 集計の定義を明確に
- **進捗率** = 実施済み（untested 以外）÷ 全件
- **合格率** = passed ÷（passed + failed + blocked）
  ― skipped は「判定していない」ため、合格率の分母に含めません

定義はテスト（`tests/Feature/TestRunApiTest.php`）でも保証しています。

### 3. 役割を分けた構成
| 層 | 場所 | 役割 |
|---|---|---|
| FormRequest | `app/Http/Requests` | 入力チェック（必須・文字数・Enum・別プロジェクトのデータ紐づけ防止など） |
| Service | `app/Services` | 複数テーブルにまたがる処理（テスト実行の作成、集計） |
| Resource | `app/Http/Resources` | レスポンスの形式をそろえる |

### 4. データの整合性
- 1回のテスト実行で同じテストケースの結果が重複しないよう、一意制約をかけています
- 不具合に紐づけるテスト結果は、同じプロジェクトのものだけを許可しています
- テスト実行の作成はトランザクションで行い、途中で失敗しても中途半端なデータが残らないようにしています

ER図は [docs/er-diagram.md](docs/er-diagram.md) を参照してください。

## テスト方針
QAの経験を活かし、正常系だけでなく次の観点でテストを書いています。

| 観点 | 例 |
|---|---|
| 境界値 | プロジェクト名 100文字は登録でき、101文字は 422 |
| 異常系 | 必須項目の欠落・定義外の値は 422、存在しないIDは 404 |
| 状態遷移 | 不具合ステータスの全組み合わせ（5×5＝25通り）をデータプロバイダで網羅 |
| 条件付き必須 | failed・blocked のときだけ理由（note）を必須にする |
| データの分離 | 別プロジェクトのテストケースや不具合が一覧・集計に混ざらない |
| ゼロ件 | 結果が0件でも集計が0除算にならない |

```bash
composer test
```

## API一覧

すべて `/api` から始まり、JSON でやり取りします。

| メソッド | パス | 内容 |
|---|---|---|
| GET / POST | `/projects` | プロジェクト一覧・作成 |
| GET / PATCH / DELETE | `/projects/{id}` | プロジェクト詳細・更新・削除 |
| GET / POST | `/projects/{id}/test-cases` | テストケース一覧（`?priority=` `?is_active=`）・作成 |
| GET / PATCH / DELETE | `/test-cases/{id}` | テストケース詳細・更新・削除 |
| GET / POST | `/projects/{id}/test-runs` | テスト実行一覧・作成 |
| GET / DELETE | `/test-runs/{id}` | テスト実行詳細（結果を含む）・削除 |
| GET | `/test-runs/{id}/summary` | 進捗率・合格率の集計 |
| PATCH | `/test-results/{id}` | テスト結果の記録 |
| GET / POST | `/projects/{id}/defects` | 不具合一覧（`?status=` `?severity=`）・作成 |
| GET | `/projects/{id}/defects/summary` | 不具合のステータス別・重要度別の集計 |
| GET / PATCH / DELETE | `/defects/{id}` | 不具合詳細・更新・削除 |
| PATCH | `/defects/{id}/status` | 不具合のステータス変更 |

### 使用例

```bash
# テスト結果を「失敗」で記録する（理由が必須）
curl -X PATCH http://localhost:8000/api/test-results/2 \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"status": "failed", "note": "エラーメッセージが表示されない"}'

# 進捗の集計
curl http://localhost:8000/api/test-runs/1/summary
```

```json
{
  "data": {
    "test_run_id": 1,
    "total": 5,
    "executed": 4,
    "by_status": { "untested": 1, "passed": 2, "failed": 1, "blocked": 1, "skipped": 0 },
    "progress_rate": 80,
    "pass_rate": 50
  }
}
```

```bash
# 許可されていない遷移は 422 になる
curl -X PATCH http://localhost:8000/api/defects/1/status \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"status": "closed"}'
# => {"message":"ステータスを in_progress から closed に変更することはできません。", ...}
```

## セットアップ

```bash
git clone https://github.com/qedhourse580/test-case-manager-api.git
cd test-case-manager-api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed   # サンプルデータ（ECサイトの回帰テスト）を投入
php artisan serve
```

## AI駆動開発について
開発には Claude Code を使用しました。進め方は次のとおりです。

1. 仕様（エンティティ、状態遷移、集計の定義）を自分で決めて文章にする
2. AIに実装とテストを書かせ、テスト結果と差分を自分で確認してから取り込む
3. 開発ルールは [CLAUDE.md](CLAUDE.md) にまとめ、AIが毎回同じ方針で実装するようにしている

## 今後の予定
- 認証（Laravel Sanctum）とユーザーごとの権限管理
- テストケースのCSVインポート・エクスポート
- Playwright による API の E2E テスト
- OpenAPI（Swagger）によるAPI仕様書の自動生成

## License
MIT
