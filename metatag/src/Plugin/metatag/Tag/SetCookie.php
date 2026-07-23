<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * Provides a plugin for the 'set-cookie' meta tag.
 *
 * @MetatagTag(
 *   id = "set_cookie",
 *   label = @Translation("Set cookie"),
 *   description = @Translation("Sets a cookie on the visitor's browser. Can be in either NAME=VALUE format, or a more verbose format including the path and expiration date. See <a href='https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Set-Cookie'>MDN documentation on Set-Cookie</a> for full details on the syntax."),
 *   name = "set-cookie",
 *   group = "advanced",
 *   weight = 5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SetCookie extends MetaHttpEquivBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
