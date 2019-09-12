<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// if (!function_exists('url')) {
//     function url($val)
//     {
//         if (filter_var($val, FILTER_VALIDATE_URL)) {
//             return '<a href='.$val.' target="_blank">'.$val.'</a>';
//         }
//
//         return $val;
//     }
// }
?>
<dl class="dl-horizontal">
    <?php foreach ($event['extra']['stat'] as $key => $value) : ?>
        <dt><?php echo $key; ?></dt>
        <?php if (is_array($value)): ?>
            <dl class="dl-horizontal small">
                <?php foreach ($value as $k => $v) : ?>
                    <dt><?php echo $k; ?></dt>
                    <?php if (is_array($v)): ?>
                        <dl class="dl-horizontal small">
                            <?php foreach ($v as $kb => $vb) : ?>
                                <dt><?php echo $kb; ?></dt>
                                <dd><?php echo $vb; ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php else: ?>
                        <dd><?php echo $v; ?></dd>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        <?php else: ?>
            <dd><?php echo $value; ?></dd>
        <?php endif; ?>
    <?php endforeach; ?>
</dl>
