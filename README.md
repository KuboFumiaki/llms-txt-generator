# LLMs.txt Generator for WP

WordPressサイトのコンテンツからLLMs.txtファイルを自動生成するプラグインです。AIや機械学習モデル（LLMs: Large Language Models）がサイト内容を理解するのに最適なフォーマットで出力します。

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.3%2B-blue.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)

## 📋 主な機能

- **自動生成**: 投稿の公開・更新時に自動的にLLMS.txtファイルを生成
- **カスタムテキスト**: ファイル上部に表示するカスタムテキストの設定
- **投稿タイプ管理**: 出力する投稿タイプの選択と順序の設定
- **カテゴリ分類**: ブログ投稿をカテゴリ別に自動分類
- **文字コード選択**: UTF-8またはShift-JISでの出力に対応
- **手動生成**: 管理画面から手動でファイル生成も可能
- **セキュリティ**: nonce検証と権限チェックによる安全な操作

## 🚀 インストール

### 方法1: 手動インストール

1. このリポジトリをダウンロードまたはクローン
```bash
git clone https://github.com/KuboFumiaki/llms-txt-generator.git
```

2. プラグインフォルダを `/wp-content/plugins/` にアップロード

3. WordPress管理画面でプラグインを有効化

### 方法2: ZIPファイルでインストール

1. リポジトリから最新版をダウンロード
2. WordPress管理画面の「プラグイン」→「新規追加」→「プラグインのアップロード」
3. ZIPファイルをアップロードして有効化

## ⚙️ 使用方法

1. プラグインを有効化後、管理画面の「ツール」→「LLMS.txt Generator」にアクセス
2. 各種設定を行う：
   - **カスタムテキスト**: ファイル上部に表示するテキスト
   - **文字コード**: UTF-8またはShift-JISを選択
   - **投稿タイプ**: 出力する投稿タイプと順序を設定
3. 「LLMS.txtを生成」ボタンをクリック
4. 生成されたファイルに `https://yoursite.com/llms.txt` でアクセス

## 📄 生成されるファイル形式

```markdown
# サイト名

サイトの説明

※ここより上部はカスタムテキストに置き換え可能※

# 最終更新: 2025-01-01 12:00:00

## 投稿

### カテゴリ名
- [記事タイトル](URL):記事の要約...

## カスタム投稿タイプ
- [投稿タイトル](URL):投稿の要約...
```

## 🔧 システム要件

- **WordPress**: 5.0以上
- **PHP**: 7.3以上
- **拡張機能**: mbstring（文字コード変換用）

## 🛡️ セキュリティ機能

- nonce検証による CSRF 攻撃対策
- 権限チェック（manage_options権限が必要）
- 入力データのサニタイゼーション
- エスケープ処理による XSS 対策

## 📁 ファイル構成

```
llms-txt-generator/
├── index.php          # メインプラグインファイル
├── uninstall.php      # アンインストール処理
├── readme.txt         # WordPress Plugin Directory用
├── README.md          # GitHub用説明ファイル
└── .gitignore         # Git除外設定
```

## 🔄 更新履歴

### v1.0.0 (2025-08-12)
- 初回リリース
- 基本的なLLMS.txt生成機能
- 管理画面UI
- セキュリティ機能の実装

## 🤝 貢献方法

1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add some amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

## 🐛 バグ報告・機能リクエスト

バグ報告や機能リクエストは [Issues](https://github.com/KuboFumiaki/llms-txt-generator/issues) でお願いします。

## 📞 サポート

- [GitHub Issues](https://github.com/KuboFumiaki/llms-txt-generator/issues)
- [WordPress Plugin Directory](https://wordpress.org/plugins/llms-txt-generator/) (申請予定)

---

**LLMs.txt Generator for WP** - AI時代のWordPressコンテンツ管理を支援します 🤖
