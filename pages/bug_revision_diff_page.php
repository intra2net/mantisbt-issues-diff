<?php

/**
 * IssuesDiff - a MantisBT plugin that adds a visual diff between revisions
 *
 * You should have received a copy of the GNU General Public License
 * along with IssuesDiff.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (C) 2020 Intra2net AG - www.intra2net.com
 */

echo '<link rel="stylesheet" type="text/css" href="', plugin_file( 'css/diff_style.css' ), '"/>';

$plugin_basename = plugin_get_current();
$php_diff_lib_path = 'vendor/php-diff/lib/';

$diff_php = plugin_file_path($php_diff_lib_path . 'Diff.php', $plugin_basename);
$inline_php = plugin_file_path($php_diff_lib_path . 'Diff/Renderer/Html/Inline.php', $plugin_basename);

require_once ('core.php');
require_once($diff_php);
require_once($inline_php);

$f_bug_id = gpc_get_int('bug_id', 0);
$f_bugnote_id = gpc_get_int('bugnote_id', 0);
$f_rev_id = gpc_get_int('rev_id', 0);

$previous_rev_id = gpc_get_int('prev', 0);
$last_rev_id = gpc_get_int('last', 0);

$page = gpc_get_string('page');

$t_title = '';

if ($f_bug_id) {
    $t_bug_id = $f_bug_id;
    $t_bug_data = bug_get($t_bug_id, true);
    $t_bug_revisions = array_reverse(bug_revision_list($t_bug_id) , true);
    $t_title = lang_get('issue_id') . $t_bug_id;
}
else if ($f_bugnote_id) {
    $t_bug_id = bugnote_get_field($f_bugnote_id, 'bug_id');
    $t_bug_data = bug_get($t_bug_id, true);
    $t_bug_revisions = bug_revision_list($t_bug_id, REV_ANY, $f_bugnote_id);
    $t_title = lang_get('bugnote') . ' ' . $f_bugnote_id;
}
else if ($f_rev_id) {
    $t_bug_revisions = bug_revision_like($f_rev_id);

    if (count($t_bug_revisions) < 1) {
        trigger_error(ERROR_GENERIC, ERROR);
    }

    $t_bug_id = $t_bug_revisions[$f_rev_id]['bug_id'];
    $t_bug_data = bug_get($t_bug_id, true);
    $t_title = lang_get('issue_id') . $t_bug_id;
}
else {
    trigger_error(ERROR_GENERIC, ERROR);
}

if (!$last_rev_id) {
    $last_rev_id = reset($t_bug_revisions)['id'];
    $previous_rev_id = next($t_bug_revisions)['id'];

    reset($t_bug_revisions);
} else if (!$previous_rev_id) {
    while(key($t_bug_revisions) !== $last_rev_id) next($t_bug_revisions);

    $previous_rev_id = next($t_bug_revisions)['id'];
    reset($t_bug_revisions);
}

function show_diff($t_prev_revision, $t_last_revision)
{
    static $s_user_access = null;

    switch ($t_last_revision['type']) {
        case REV_DESCRIPTION:
            $t_label = lang_get('description');
            break;

        case REV_STEPS_TO_REPRODUCE:
            $t_label = lang_get('steps_to_reproduce');
            break;

        case REV_ADDITIONAL_INFO:
            $t_label = lang_get('additional_information');
            break;

        case REV_BUGNOTE:
            if (is_null($s_user_access)) {
                $s_user_access = access_has_bug_level(config_get('private_bugnote_threshold') , $t_last_revision['bug_id']);
            }

            if (!$s_user_access) {
                return null;
            }

            $t_label = lang_get('bugnote');
            break;

        default:
            $t_label = '';
    }

    $new_revision_text = explode("\n", $t_last_revision['value']);
    $old_revision_text = explode("\n", $t_prev_revision['value']);

    $diff_options = array(
        //'ignoreWhitespace' => true,
        //'ignoreCase' => true,
    );

    $diff = new Diff($old_revision_text, $new_revision_text, $diff_options);

    $renderer = new Diff_Renderer_Html_Inline;

    ?>

    <tr <?php echo helper_alternate_class(); ?>>
        <td class="category">
            <?php
                echo $t_label . ' ' . plugin_lang_get('diff');
            ?>
        </td>
        <td colspan="3" class="issue-diff-area">
            <?php
                echo $diff->render($renderer);
            ?>
        </td>
    </tr>

    <?php
}

html_page_top(bug_format_summary($t_bug_id, SUMMARY_CAPTION));
print_recently_visited();
?>

<br />

<?php

function get_options_list($revisions, $selected_id) {
    $options_revisions = "";

    foreach($revisions as $t_rev) {
        $t_by_string = sprintf(lang_get('revision_by'),
                                string_display_line(date(config_get('normal_date_format'), $t_rev['timestamp'])),
                                string_display_line(user_get_name($t_rev['user_id'])));

        $rev_id = $t_rev["id"];
        $selected = $rev_id == $selected_id ? "selected" : "";

        $options_revisions .= "<option value=\"$rev_id\" $selected>$t_by_string</option>";
    }

    return $options_revisions;
}

?>

<table class="width100" cellspacing="1">
    <tr>
        <td class="form-title" colspan="2">
            <?php
            echo plugin_lang_get('menu_title') , ': ', $t_title;
            ?>
        </td>
        <td class="right" colspan="2">
            <?php
            print_bracket_link('view.php?id=' . $t_bug_id, lang_get('back_to_issue'));
            ?>
        </td>
    </tr>

    <tr <?php echo helper_alternate_class(); ?>>
        <td class="category" width="15%">
            <?php
                echo lang_get('summary');
            ?>
        </td>
        <td colspan="3">
            <?php
                echo bug_format_summary($t_bug_id, SUMMARY_FIELD);
            ?>
        </td>
    </tr>

    <tr class="spacer">
        <td>
            <a name="r<?php echo $t_revision['id']; ?>"></a>
        </td>
    </tr>

    <tr <?php echo helper_alternate_class(); ?>>
        <td class="category">
            <?php
                echo plugin_lang_get('revision_range');
            ?>
        </td>
        <td colspan="2">
            <form method="get" class="diff-form">
                <input type="hidden" name="page" value="<?php echo $page ?>" />

                <?php
                    if ($f_bug_id) {
                        ?>
                            <input type="hidden" name="bug_id" value="<?php echo $f_bug_id ?>" />
                        <?php
                    } else if ($f_bugnote_id) {
                        ?>
                            <input type="hidden" name="bugnote_id" value="<?php echo $f_bugnote_id ?>" />
                        <?php
                    } else if ($f_rev_id) {
                        ?>
                            <input type="hidden" name="rev_id" value="<?php echo $f_rev_id ?>" />
                        <?php
                    }
                ?>

                <select name="prev">
                    <?php echo get_options_list($t_bug_revisions, $previous_rev_id); ?>
                </select>
                <span class="issue-diff-separator"> ... </span>
                <select name="last">
                    <?php
                        reset($t_bug_revisions);
                        echo get_options_list($t_bug_revisions, $last_rev_id);
                    ?>
                </select>

                <input type="submit" value="<?php echo plugin_lang_get('view') ?>" class="diff-form-submit" />

            </form>
        </td>
    </tr>

    <?php
        $previous_rev = $t_bug_revisions[$previous_rev_id];
        $last_rev = $t_bug_revisions[$last_rev_id];

        show_diff($previous_rev, $last_rev);
    ?>

</table>

<?php
html_page_bottom();