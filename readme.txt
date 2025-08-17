=== LLMs.txt Generator for WordPress ===
Contributors: yourusername
Tags: llm, ai, machine learning, content export, markdown
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPressサイトのコンテンツからLLMS.txtファイルを自動生成するプラグイン。AIや機械学習モデルがサイト内容を理解するのに最適なフォーマットで出力します。

== Description ==

LLMs.txt Generator for WordPressは、WordPressサイトのコンテンツをAIや機械学習モデル（LLMs: Large Language Models）が読みやすい形式でエクスポートするプラグインです。

= 主な機能 =

* **自動生成**: 投稿の公開・更新時に自動的にLLMS.txtファイルを生成
* **カスタムテキスト**: ファイル上部に表示するカスタムテキストの設定
* **投稿タイプ管理**: 出力する投稿タイプの選択と順序の設定
* **カテゴリ分類**: ブログ投稿をカテゴリ別に自動分類
* **文字コード選択**: UTF-8またはShift-JISでの出力に対応
* **手動生成**: 管理画面から手動でファイル生成も可能

= 生成されるファイル =

プラグインは以下の形式でllms.txtファイルをサイトルートに生成します：

* カスタムテキスト（設定されている場合）
* 最終更新日時
* 投稿タイプ別のコンテンツ一覧
* ブログ投稿はカテゴリ別に分類
* 各投稿にはタイトル、URL、要約を含む

= 使用方法 =

1. プラグインを有効化
2. 管理画面の「ツール」→「LLMs.txt Generator」で設定
3. カスタムテキスト、文字コード、投稿タイプを設定
4. 「LLMS.txtを生成」ボタンをクリック
5. サイトルート（例: https://yoursite.com/llms.txt）でファイルにアクセス可能

= システム要件 =

* WordPress 5.0以上
* PHP 7.3以上
* mbstring拡張（文字コード変換用）

== Installation ==

= 自動インストール =

1. WordPress管理画面で「プラグイン」→「新規追加」
2. 「LLMs.txt Generator for WordPress」を検索
3. 「今すぐインストール」→「有効化」

= 手動インストール =

1. プラグインファイルをダウンロード
2. `/wp-content/plugins/llms-txt-generator`ディレクトリにアップロード
3. WordPress管理画面でプラグインを有効化

= 設定 =

1. 「ツール」→「LLMs.txt Generator」にアクセス
2. 必要に応じてカスタムテキストを設定
3. 文字コード（UTF-8またはShift-JIS）を選択
4. 出力する投稿タイプと順序を設定
5. 「LLMS.txtを生成」をクリック

== Frequently Asked Questions ==

= LLMS.txtファイルはどこに保存されますか？ =

ファイルはWordPressサイトのルートディレクトリ（wp-config.phpと同じ場所）に保存され、https://yoursite.com/llms.txt でアクセスできます。

= 自動生成はいつ行われますか？ =

投稿の公開時と更新時に自動的に実行されます。手動での生成も管理画面から可能です。

= どの投稿タイプが出力されますか？ =

デフォルトでは全ての公開投稿タイプが含まれます（固定ページを除く）。設定画面で特定の投稿タイプのみに制限することも可能です。

= 文字コードは変更できますか？ =

はい、UTF-8（推奨）またはShift-JISから選択できます。デフォルトはShift-JISです。

= 大量の投稿がある場合の影響は？ =

ファイル生成は投稿数に比例して時間がかかります。非常に大量の投稿がある場合は、出力する投稿タイプを制限することをお勧めします。

== Screenshots ==

1. 管理画面 - カスタムテキスト設定
2. 管理画面 - 文字コード設定
3. 管理画面 - 投稿タイプ設定と順序変更
4. 管理画面 - ファイル生成画面
5. 生成されたLLMS.txtファイルの例

== Changelog ==

= 1.0.0 =
* 初回リリース
* 基本的なLLMS.txt生成機能
* カスタムテキスト設定
* 投稿タイプ管理
* 文字コード選択（UTF-8/Shift-JIS）
* カテゴリ別投稿分類
* 自動生成機能
* 手動生成機能

== Upgrade Notice ==

= 1.0.0 =
初回リリースです。インストール後、管理画面で設定を行ってください。

== Development ==

このプラグインはGitHubで開発されています：
https://github.com/KuboFumiaki/llms-txt-generator

バグ報告や機能リクエストはGitHubのIssuesでお願いします。

== License ==

このプラグインはGPL v2ライセンスの下で公開されています。
