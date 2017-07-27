<?php

# IssuesDiff - a MantisBT plugin that adds a visual diff between revisions
#
# You should have received a copy of the GNU General Public License
# along with IssuesDiff.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright Copyright (C) 2017 Samir Aguiar for Intra2net AG - www.intra2net.com
 */

class IssuesDiffPlugin extends MantisPlugin {

    public function register() {
        $this->name = plugin_lang_get("title");
        $this->description = plugin_lang_get("description");
        $this->page = '';

        $this->version = "1.0";
        $this->requires = array(
            "MantisCore" => "1.2.11",
        );

        $this->author = "Samir Aguiar";
        $this->contact = "samirjaguiar@gmail.com";
        $this->url = "https://github.com/samiraguiar/issues-diff";
    }

    public function hooks() {
        return array(
            "EVENT_MENU_ISSUE" => "get_menu_option",
            "EVENT_VIEW_BUG_DETAILS" => "inject_script"
        );
    }

    public function inject_script() {
        $plugin_basename = plugin_get_current();
        $script_path = plugin_file_path('js/add_diff_view_link.js', $plugin_basename);
        $script_contents = file_get_contents($script_path);

        $menu_title = plugin_lang_get("menu_title");
        $diff_page_url = plugin_page("bug_revision_diff_page", true);

        $script = "(function() {" .
                    "var linkText = '$menu_title';" .
                    "var diffPageUrl = '$diff_page_url';" .
                    $script_contents .
                  "})();";

        echo "<script> $script </script>";
    }
}