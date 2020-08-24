<?php

 /**
  * Note Diff - a MantisBT plugin to diff revisions of notes and issue descriptions
  *
  * You should have received a copy of the GNU General Public License
  * along with Note Diff.  If not, see <http://www.gnu.org/licenses/>.
  *
  * @copyright Copyright (C) 2020 Intra2net AG - www.intra2net.com
  */

class NoteDiffPlugin extends MantisPlugin {

    public function register() {
        $this->name = plugin_lang_get("title");
        $this->description = plugin_lang_get("description");
        $this->page = '';

        $this->version = "2.0.0";
        $this->requires = array(
            "MantisCore" => "2.1.0",
        );

        $this->author = "Intra2net AG";
        $this->contact = "opensource@intra2net.com";
        $this->url = "https://github.com/intra2net/mantisbt-note-diff";
    }

    public function hooks() {
        return array(
            "EVENT_VIEW_BUG_DETAILS" => "inject_script"
        );
    }

    public function inject_script() {
        $menu_title = plugin_lang_get("menu_title");
        $diff_page_url = plugin_page("bug_revision_diff_page", true);

        echo '<a id="view-diff-anchor" data-base-href="'
             . $diff_page_url . '" style="display: none">' . $menu_title . '</a>';

        echo '<script src="' . plugin_file('js/add_diff_view_link.js') . '"></script>';
    }
}