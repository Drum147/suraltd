<?php

/**
 *  This file is part of PHP-Typography.
 *
 *  Copyright 2014-2019 Peter Putzer.
 *  Copyright 2009-2011 KINGdesk, LLC.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 *  ***
 *
 *  @package mundschenk-at/php-typography
 *  @license http://www.gnu.org/licenses/gpl-2.0.html
 */
namespace PHP_Typography\Fixes\Node_Fixes;

use PHP_Typography\DOM;
use PHP_Typography\RE;
use PHP_Typography\Settings;
use PHP_Typography\Strings;
use PHP_Typography\U;
/**
 * Collapse spaces (if enabled).
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @since 5.0.0
 */
class Space_Collapse_Fix extends \PHP_Typography\Fixes\Node_Fixes\Abstract_Node_Fix
{
    const COLLAPSE_NORMAL_SPACES = '/[' . \PHP_Typography\RE::NORMAL_SPACES . ']+/Sxu';
    const COLLAPSE_NON_BREAKABLE_SPACES = '/(?:[' . \PHP_Typography\RE::NORMAL_SPACES . ']|' . \PHP_Typography\RE::HTML_SPACES . ')*' . \PHP_Typography\U::NO_BREAK_SPACE . '(?:[' . \PHP_Typography\RE::NORMAL_SPACES . ']|' . \PHP_Typography\RE::HTML_SPACES . ')*/Sxu';
    const COLLAPSE_OTHER_SPACES = '/(?:[' . \PHP_Typography\RE::NORMAL_SPACES . '])*(' . \PHP_Typography\RE::HTML_SPACES . ')(?:[' . \PHP_Typography\RE::NORMAL_SPACES . ']|' . \PHP_Typography\RE::HTML_SPACES . ')*/Sxu';
    const COLLAPSE_SPACES_AT_START_OF_BLOCK = '/\\A(?:[' . \PHP_Typography\RE::NORMAL_SPACES . ']|' . \PHP_Typography\RE::HTML_SPACES . ')+/Sxu';
    /**
     * Apply the fix to a given textnode.
     *
     * @param \DOMText $textnode Required.
     * @param Settings $settings Required.
     * @param bool     $is_title Optional. Default false.
     */
    public function apply(\DOMText $textnode, \PHP_Typography\Settings $settings, $is_title = \false)
    {
        if (empty($settings[\PHP_Typography\Settings::SPACE_COLLAPSE])) {
            return;
        }
        // Cache textnode content.
        $node_data = $textnode->data;
        // Replace spaces.
        $node_data = \preg_replace(
            [
                // Normal spacing.
                self::COLLAPSE_NORMAL_SPACES,
                // Non-breakable space get's priority. If non-breakable space exists in a string of spaces, it collapses to a single non-breakable space.
                self::COLLAPSE_NON_BREAKABLE_SPACES,
                // For any other spaceing, replace with the first occurance of an unusual space character.
                self::COLLAPSE_OTHER_SPACES,
            ],
            [
                // @codeCoverageIgnoreStart
                ' ',
                \PHP_Typography\U::NO_BREAK_SPACE,
                '$1',
            ],
            // @codeCoverageIgnoreEnd
            $node_data
        );
        // Remove all spacing at beginning of block level elements.
        if (null === \PHP_Typography\DOM::get_previous_textnode($textnode)) {
            $node_data = \preg_replace(self::COLLAPSE_SPACES_AT_START_OF_BLOCK, '', $node_data);
        }
        // Restore textnode content.
        $textnode->data = $node_data;
    }
}
