<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf\Html;

use App\Pdf\Interfaces\PdfColorInterface;
use App\Pdf\Traits\PdfColorTrait;

/**
 * HTML color name enumeration.
 */
enum HtmlColorName: string implements PdfColorInterface
{
    use PdfColorTrait;

    case ALICE_BLUE = '#F0F8FF';
    case ANTIQUE_WHITE = '#FAEBD7';
    case AQUAMARINE = '#7FFFD4';
    case AZURE = '#F0FFFF';
    case BEIGE = '#F5F5DC';
    case BISQUE = '#FFE4C4';
    case BLACK = '#000000';
    case BLANCHED_ALMOND = '#FFEBCD';
    case BLUE = '#0000FF';
    case BLUE_VIOLET = '#8A2BE2';
    case BROWN = '#A52A2A';
    case BURLY_WOOD = '#DEB887';
    case CADET_BLUE = '#5F9EA0';
    case CHARTREUSE = '#7FFF00';
    case CHOCOLATE = '#D2691E';
    case CORAL = '#FF7F50';
    case CORN_SILK = '#FFF8DC';
    case CORNFLOWER_BLUE = '#6495ED';
    case CRIMSON = '#DC143C';
    case CYAN = '#00FFFF';
    case DARK_BLUE = '#00008B';
    case DARK_CYAN = '#008B8B';
    case DARK_GOLDENROD = '#B8860B';
    case DARK_GRAY = '#A9A9A9';
    case DARK_GREEN = '#006400';
    case DARK_KHAKI = '#BDB76B';
    case DARK_MAGENTA = '#8B008B';
    case DARK_OLIVE_GREEN = '#556B2F';
    case DARK_ORANGE = '#FF8C00';
    case DARK_ORCHID = '#9932CC';
    case DARK_RED = '#8B0000';
    case DARK_SALMON = '#E9967A';
    case DARK_SEA_GREEN = '#8DBC8F';
    case DARK_SLATE_BLUE = '#483D8B';
    case DARK_SLATE_GRAY = '#2F4F4F';
    case DARK_TURQUOISE = '#00DED1';
    case DARK_VIOLET = '#9400D3';
    case DEEP_PINK = '#FF1493';
    case DEEP_SKY_BLUE = '#00BFFF';
    case DIM_GRAY = '#696969';
    case DODGER_BLUE = '#1E90FF';
    case FIREBRICK = '#B22222';
    case FLORAL_WHITE = '#FFFAF0';
    case FOREST_GREEN = '#228B22';
    case FUCHSIA = '#FF00FF';
    case GAINSBORO = '#DCDCDC';
    case GHOST_WHITE = '#F8F8FF';
    case GOLD = '#FFD700';
    case GOLDENROD = '#DAA520';
    case GRAY = '#808080';
    case GREEN = '#008000';
    case GREEN_YELLOW = '#ADFF2F';
    case HONEYDEW = '#F0FFF0';
    case HOT_PINK = '#FF69B4';
    case INDIAN_RED = '#CD5C5C';
    case INDIGO = '#4B0082';
    case IVORY = '#FFFFF0';
    case KHAKI = '#F0E68C';
    case LAVENDER = '#E6E6FA';
    case LAVENDER_BLUSH = '#FFF0F5';
    case LAWN_GREEN = '#7CFC00';
    case LEMON_CHIFFON = '#FFFACD';
    case LIGHT_BLUE = '#ADD8E6';
    case LIGHT_CORAL = '#F08080';
    case LIGHT_CYAN = '#E0FFFF';
    case LIGHT_GOLDENROD_YELLOW = '#FAFAD2';
    case LIGHT_GREEN = '#90EE90';
    case LIGHT_GREY = '#D3D3D3';
    case LIGHT_PINK = '#FFB6C1';
    case LIGHT_SALMON = '#FFA07A';
    case LIGHT_SEA_GREEN = '#20B2AA';
    case LIGHT_SKY_BLUE = '#87CEFA';
    case LIGHT_SLATE_GRAY = '#778899';
    case LIGHT_STEEL_BLUE = '#B0C4DE';
    case LIGHT_YELLOW = '#FFFFE0';
    case LIME = '#00FF00';
    case LIME_GREEN = '#32CD32';
    case LINEN = '#FAF0E6';
    case MAROON = '#800000';
    case MEDIUM_AQUAMARINE = '#66CDAA';
    case MEDIUM_BLUE = '#0000CD';
    case MEDIUM_ORCHID = '#BA55D3';
    case MEDIUM_PURPLE = '#9370DB';
    case MEDIUM_SEA_GREEN = '#3CB371';
    case MEDIUM_SLATE_BLUE = '#7B68EE';
    case MEDIUM_SPRING_GREEN = '#00FA9A';
    case MEDIUM_TURQUOISE = '#48D1CC';
    case MEDIUM_VIOLET_RED = '#C71585';
    case MIDNIGHT_BLUE = '#191970';
    case MINT_CREAM = '#F5FFFA';
    case MISTY_ROSE = '#FFE4E1';
    case MOCCASIN = '#FFE4B5';
    case NAVAJO_WHITE = '#FFDEAD';
    case NAVY = '#000080';
    case OLD_LACE = '#FDF5E6';
    case OLIVE = '#808000';
    case OLIVE_DRAB = '#6B8E23';
    case ORANGE = '#FFA500';
    case ORANGE_RED = '#FF4500';
    case ORCHID = '#DA70D6';
    case PALE_GOLDENROD = '#EEE8AA';
    case PALE_GREEN = '#98FB98';
    case PALE_TURQUOISE = '#AFEEEE';
    case PALE_VIOLET_RED = '#DB7093';
    case PAPAYA_WHIP = '#FFEFD5';
    case PEACH_PUFF = '#FFDAB9';
    case PERU = '#CD853F';
    case PINK = '#FFC8CB';
    case PLUM = '#DDA0DD';
    case POWDER_BLUE = '#B0E0E6';
    case PURPLE = '#800080';
    case RED = '#FF0000';
    case ROSY_BROWN = '#BC8F8F';
    case ROYAL_BLUE = '#4169E1';
    case SADDLE_BROWN = '#8B4513';
    case SALMON = '#FA8072';
    case SANDY_BROWN = '#F4A460';
    case SEA_GREEN = '#2E8B57';
    case SEA_SHELL = '#FFF5EE';
    case SIENNA = '#A0522D';
    case SILVER = '#C0C0C0';
    case SKY_BLUE = '#87CEEB';
    case SLATE_BLUE = '#6A5ACD';
    case SNOW = '#FFFAFA';
    case SPRING_GREEN = '#00FF7F';
    case STEEL_BLUE = '#4682B4';
    case TAN = '#D2B48C';
    case TEAL = '#008080';
    case THISTLE = '#D8BFD8';
    case TOMATO = '#FF6347';
    case TURQUOISE = '#40E0D0';
    case VIOLET = '#EE82EE';
    case WHEAT = '#F5DEB3';
    case WHITE = '#FFFFFF';
    case WHITE_SMOKE = '#F5F5F5';
    case YELLOW = '#FFFF00';
    case YELLOW_GREEN = '#9ACD32';
}
