<?php
# IssuesDiff - a MantisBT plugin that adds a visual diff between revisions
#
# You should have received a copy of the GNU General Public License
# along with IssuesDiff.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright Copyright (C) 2017 Samir Aguiar for Intra2net AG - www.intra2net.com
 *
 * Parts of this code were taken from MantisBT `bug_revision_view_page.php`.
 */

require_once( 'core.php' );
require_api( 'access_api.php' );
require_api( 'bug_api.php' );
require_api( 'bugnote_api.php' );
require_api( 'bug_revision_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'form_api.php' );
require_api( 'gpc_api.php' );
require_api( 'helper_api.php' );
require_api( 'html_api.php' );
require_api( 'lang_api.php' );
require_api( 'print_api.php' );
require_api( 'string_api.php' );
require_api( 'user_api.php' );

$plugin_basename = plugin_get_current();
$php_diff_lib_path = 'vendor/php-diff/lib/';

$diff_php = plugin_file_path($php_diff_lib_path . 'Diff.php', $plugin_basename);
$inline_php = plugin_file_path($php_diff_lib_path . 'Diff/Renderer/Html/Inline.php', $plugin_basename);

require_css(plugin_file('css/diff_style.css', false));
require_once($diff_php);
require_once($inline_php);

$f_bug_id = gpc_get_int( 'bug_id', 0 );
$f_bugnote_id = gpc_get_int( 'bugnote_id', 0 );
$f_rev_id = gpc_get_int( 'rev_id', 0 );

$previous_rev_id = gpc_get_int('prev', 0);
$last_rev_id = gpc_get_int('last', 0);
$page = gpc_get_string('page');

$t_title = '';

if( $f_bug_id ) {
    $t_bug_id = $f_bug_id;
    $t_bug_data = bug_get( $t_bug_id, true );
    $t_bug_revisions = array_reverse( bug_revision_list( $t_bug_id ), true );

    $t_title = lang_get( 'issue_id' ) . $t_bug_id;

} else if( $f_bugnote_id ) {
    $t_bug_id = bugnote_get_field( $f_bugnote_id, 'bug_id' );
    $t_bug_data = bug_get( $t_bug_id, true );

    $t_bug_revisions = bug_revision_list( $t_bug_id, REV_ANY, $f_bugnote_id );

    $t_title = lang_get( 'bugnote' ) . ' ' . $f_bugnote_id;

} else if( $f_rev_id ) {
    $t_bug_revisions = bug_revision_like( $f_rev_id );

    if( count( $t_bug_revisions ) < 1 ) {
        trigger_error( ERROR_GENERIC, ERROR );
    }

    $t_bug_id = $t_bug_revisions[$f_rev_id]['bug_id'];
    $t_bug_data = bug_get( $t_bug_id, true );

    $t_title = lang_get( 'issue_id' ) . $t_bug_id;

} else {
    trigger_error( ERROR_GENERIC, ERROR );
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

    switch( $t_last_revision['type'] ) {
        case REV_DESCRIPTION:
            $t_label = lang_get( 'description' );
            break;
        case REV_STEPS_TO_REPRODUCE:
            $t_label = lang_get( 'steps_to_reproduce' );
            break;
        case REV_ADDITIONAL_INFO:
            $t_label = lang_get( 'additional_information' );
            break;
        case REV_BUGNOTE:
            if( is_null( $s_user_access ) ) {
                $s_user_access = access_has_bug_level( config_get( 'private_bugnote_threshold' ), $t_last_revision['bug_id'] );
            }

            if( !$s_user_access ) {
                return null;
            }

            $t_label = lang_get( 'bugnote' );
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

    <tr>
        <th class="category">
            <?php
                echo $t_label . ' ' . plugin_lang_get('diff');
            ?>
        </th>
        <td colspan="3" class="issue-diff-area">
            <?php
                echo $diff->render($renderer);
            ?>
        </td>
    </tr>

<?php
}

layout_page_header( bug_format_summary( $t_bug_id, SUMMARY_CAPTION ) );

layout_page_begin();

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

<div class="col-md-12 col-xs-12">
    <div class="widget-box widget-color-blue2">
        <div class="widget-header widget-header-small">
            <h4 class="widget-title lighter">
                <i class="ace-icon fa fa-history"></i>
                <?php echo lang_get( 'view_revisions' ), ': ', $t_title ?>
            </h4>
        </div>

        <div class="widget-body">
            <div class="widget-toolbox">
                <div class="btn-toolbar">
                    <div class="btn-group pull-right">
                        <?php
                            print_small_button( 'view.php?id=' . $t_bug_id, lang_get( 'back_to_issue' ) );
                        ?>
                    </div>
                </div>
            </div>

            <div class="widget-main no-padding">
                <div class="table-responsive">

                    <table class="table table-bordered table-condensed table-striped">
                        <tr>
                            <th class="category" width="15%">
                                <?php echo lang_get( 'summary' ) ?>
                            </th>
                            <td colspan="3">
                                <?php echo bug_format_summary( $t_bug_id, SUMMARY_FIELD ) ?>
                            </td>
                        </tr>

                        <tr class="spacer"></tr>

                        <tr>
                            <th class="category">
                                <?php echo plugin_lang_get('revision_range') ?>
                            </th>
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

                </div>
            </div>
        </div>
    </div>
</div>

<?php
layout_page_end();

