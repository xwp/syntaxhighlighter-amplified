# SyntaxHighlighter Amplified

Adding [AMP plugin](https://github.com/Automattic/amp-wp) support to the [SyntaxHighlighter Evolved](https://wordpress.org/plugins/syntaxhighlighter/) plugin for WordPress.

## Installation

To install via Git:

```bash
mkdir -p wp-content/plugins/syntaxhighlighter-amplified
cd wp-content/plugins/syntaxhighlighter-amplified
curl -L https://github.com/xwp/syntaxhighlighter-amplified/archive/master.tar.gz | tar --strip-components=1 -xvz
git add *
composer install
git add vendor/scrivo/highlight.php/Highlight/
git add vendor/scrivo/highlight.php/styles/default.css
git commit
```

## Credits

Created by [Weston Ruter](https://weston.ruter.net/), [XWP](https://xwp.co/).

MIT license.
