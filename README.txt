=== Suggest Post Tags ===
Contributors: grandeljay
Tags: suggest, post, tags
Donate link: https://github.com/sponsors/grandeljay
Requires at least: 6.1
Tested up to: 6.1
Requires PHP: 8
Stable tag: trunk
License: GNU Affero General Public License v3.0
License URI: https://github.com/grandeljay/grandeljay-suggest-post-tags/blob/main/LICENSE

The Suggest Post Tags plugins helps you reduce the amount of tags you are using by suggesting similar, existing tags.

== Description ==

How it works

Using an API, the terms you search for are looked up in the [Merriam-Webster Dictionary](https://www.merriam-webster.com/). If you have a post tag which matches one of the Synonyms, it will be suggested.

![Hello](/src/assets/img/suggest-tags.png)

In conclusion, if your tags not being suggested, they are likely not listed as a synonym on the Merriam-Webster Dictionary.

Request API access

In order to use the Merriam-Webster API, you need to [sign-up for an API key](https://dictionaryapi.com/register/index). When you have your key, specify it in the settings under:
- Settings
  - Suggest Post Tags
