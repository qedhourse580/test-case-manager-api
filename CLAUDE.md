# CLAUDE.md

このリポジトリで Claude Code を使って開発するときのルールです。

## プロジェクト概要
テストケース・テスト実行・不具合を管理する REST API（Laravel 13 / PHP 8.3+）。

## コマンド
- テスト実行：`composer test`（または `vendor/bin/phpunit`）
- マイグレーションとサンプルデータ投入：`php artisan migrate:fresh --seed`
- ルート一覧：`php artisan route:list --path=api`

## 設計ルール
- コントローラーは薄く保つ。入力チェックは FormRequest、複数テーブルにまたがる処理は `app/Services` に置く
- レスポンスは必ず API Resource（`app/Http/Resources`）を通して返す
- ステータスや優先度などの決まった値は `app/Enums` の Enum で定義し、文字列を直接書かない
- 不具合のステータス変更は `PATCH /api/defects/{id}/status` だけで行う。遷移ルールは `DefectStatus::allowedTransitions()` に集約する
- 一覧 API はページネーション（20件）を必須とする

## テストのルール
- 機能を追加・変更したら、必ず Feature テストを追加・更新してからコミットする
- 正常系だけでなく、境界値（文字数の上限など）・異常系（422 / 404）・状態遷移の不可パターンをテストする
- テスト名は日本語で、何を保証するテストかが分かるように書く
- 全テストが通ることを確認してからコミットする

## AIに任せないこと
- 仕様（状態遷移・集計の定義）の決定は人が行い、README とテストに明記してから実装を依頼する
- 生成されたコードは、テスト結果と差分を人が確認してからコミットする
