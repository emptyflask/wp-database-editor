<?php
/*
Plugin Name:[GWA] Database Editor (less dangerous version)
Plugin URI: http://code4cookies.com
Description:A plugin to access and edit all your database fields.
Version: 1.0a
Author: G.J.P.
Author URI: http://Code4Cookies.com
*/

/*
		Copyright 2009	G.J.P	 (email : cookies@getwebactive.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	 02110-1301	 USA
*/

add_action('init','db_gwa_echo');
 
function db_gwa_echo() {
	global $wpdb;
	if($_REQUEST['db_gwa_echo']==1) {
		$fields = $wpdb->get_results("show fields from ".$wpdb->escape($_REQUEST['t']));
		$qry="UPDATE `".$_REQUEST['t']."` SET `".$_POST['field']."`='".$wpdb->escape($_POST['value'])."' WHERE `".$fields[0]->Field."` = '".$wpdb->escape($_POST['id'])."'";
		$suc = $wpdb->query($qry);
		# foreach($_POST as $k=>$v) 
		#		 echo $k."=".$v;
		if($suc)
		 echo '<span style="color:#0f0">'.stripslashes($_POST['value']).'</span>';
		else
		 echo '<span style="color:#f00">'.stripslashes($_POST['value']).'</span>';
		exit;
	}
}

function db_gwa_build_table_list() {
	global $wpdb;
	$wpdb->show_errors();
	$prefix = $wpdb->prefix;
	$tables = $wpdb->get_results("show tables");
	$database = $wpdb->col_info[0]->name;
	echo '<div style="margin-left:10px;"><form action="" name="" method="POST"><br /><select style="font-size:14pt;font-family:Tahoma,Sans-serif" name="db_gwa_selected">'."\n";
	foreach ($tables as $t){
		if(!preg_match('/^wp_/', $t->$database))
		echo '<option value="'.$t->$database.'">'.$t->$database.'</option>'."\n";
	}
	echo '</select>&nbsp;<input type="submit" class="button" name="submit" value="SUBMIT"></form></div>';
}

function headout() {
?>
<div class="wrap">
	<div id="content">
		<h1 style="position:relative;float:left;width:300px;">[GWA] db Editor</h1>
		<?php db_gwa_build_table_list(); ?>
		<br /><hr style="color:#666">
	</div>
	<p>This is a <strong>dangerous &amp; powerful</strong> direct database editor utilizing <a href="http://www.millstream.com.au/view/code/tablekit" target="_blank">TableKit</a> and AJAX for fast and easy data updates. Be careful, as there is no undo.</p>
<?php
}

function admin_db_gwa_options() {
	global $wpdb;
	$wpdb->show_errors();

	if($query=$_POST['db_gwa_sql_query']) {
		if($_POST['db_gwa_query_type']=='Row') {
			$result = $wpdb->get_results($query);
		} else if($_POST['db_gwa_query_type']=='Variable') {
			$result = $wpdb->get_var($query);
		} else if($_POST['db_gwa_query_type']=='Query') {
			$result = $wpdb->query($query);
		}
		headout();
		echo '<br /><textarea cols="80" rows="40">';
		print_r($result);
		echo '</textarea><br /><br />';
	} elseif($_POST['db_gwa_agree']) {
		add_option('db_gwa_agree',1);
		headout();
	} elseif(!get_option('db_gwa_agree')) {
		headout();
		echo '<form action="" method="POST"><input type="submit" value="I ACCEPT THE RISK OF USING THIS PLUGIN!" name="db_gwa_agree"></form>';
	} elseif(isset($_POST['db_gwa_selected']) && get_option('db_gwa_agree')) {
		$fields = $wpdb->get_results("show fields from ".$wpdb->escape($_POST['db_gwa_selected']));
		foreach($fields as $f) {
			$col[] = $f->Field;
			$col_type[] = $f->Type;
			$col_null[] = $f->Null;
			$col_key[] = $f->Key;
			$col_def[] = $f->Default;
			$col_extra[] = $f->Extra;
		}

		echo '<div class="wrap">';
		echo '<div id="content">';
		echo '<h1 style="position:relative;float:left;width:300px;">[GWA] db Editor</h1>';
		db_gwa_build_table_list();
		echo '<br /><hr style="color:#666">Click a cell (value) to update the field. Click "Ok" to commit changes to the database. UPDATES ARE IRREVERSIBLE!<hr style="color:#666">';
		echo '<table class="sortable resizable editable">';
		echo '<thead><tr>';
		for($i=0;$i<count($col);$i++) {
			echo '<th class="sortfirstdesc" id="'.$col[$i].'">'.$col[$i].'</th>';
		}
		echo '</tr></thead>';
		echo '<tbody>';

		$x_table = $_POST['db_gwa_selected'];
		$rows = $wpdb->get_results("SELECT * FROM ".$wpdb->escape($_POST['db_gwa_selected']));
		foreach($rows as $rr) {
			echo '<tr id="'.$rr->$col[0].'">';
			foreach($rr as $r) {
				echo '<td>'.htmlentities($r).'</td>';
				$z++; 
			}
			echo '</tr>';
		}
?>
				</tbody>
			</table>

			<p><small><em>[GWA] db Editor from <a href="http://code4cookies.com">Code4Cookies.com</a> uses <a href="http://www.millstream.com.au/view/code/tablekit/">TableKit!</a></em></small></p>			
						
		</div>
		<script type="text/javascript" src="<?php echo get_bloginfo('wpurl') ?>/wp-content/plugins/gwa-db-editor/js/prototype.js"></script>
		<script type="text/javascript" src="<?php echo get_bloginfo('wpurl') ?>/wp-content/plugins/gwa-db-editor/js/fabtabulous.js"></script>
		<script type="text/javascript" src="<?php echo get_bloginfo('wpurl') ?>/wp-content/plugins/gwa-db-editor/js/tablekit.js"></script>
		<script type="text/javascript">
			TableKit.Sortable.addSortType(new TableKit.Sortable.Type('status', {
					pattern : /^[New|Assigned|In Progress|Closed]$/,
					normal : function(v) {
						var val = 4;
						switch(v) {
							case 'New':
								val = 0;
								break;
							case 'Assigned':
								val = 1;
								break;
							case 'In Progress':
								val = 2;
								break;
							case 'Closed':
								val = 3;
								break;
						}
						return val;
					}
				}
			));
			TableKit.options.editAjaxURI = '<?php echo get_bloginfo('wpurl') ?>/?db_gwa_echo=1&t=<?=$x_table;?>'
			TableKit.Editable.selectInput('urgency', {}, [
						['1','1'],
						['2','2'],
						['3','3'],
						['4','4'],
						['5','5']
					]);
			TableKit.Editable.multiLineInput('title');
			var _tabs = new Fabtabs('tabs');
			$$('a.next-tab').each(function(a) {
				Event.observe(a, 'click', function(e){
					Event.stop(e);
					var t = $(this.href.match(/#(\w.+)/)[1]+'-tab');
					_tabs.show(t);
					_tabs.menu.without(t).each(_tabs.hide.bind(_tabs));
				}.bindAsEventListener(a));
			});
		</script>
		</div>
<?php
	} else {
		headout();
	}
}

function sqlbox() {
	echo '<form action="" method="POST" name="db_gwa_sql">Query Type: <select name="db_gwa_query_type"><option>Variable</option><option>Row</option><option>Query</option></select><br /><textarea cols="80" rows="5" name="db_gwa_sql_query"></textarea><br /><input type="submit" value="<<< Query >>>"></form>';
}

function modify_db_gwa_menu() {
	add_options_page(
		'db_gwa',
		'[GWA] db',
		'manage_options',
		__FILE__,
		'admin_db_gwa_options'
	);
}

function db_gwa_print_scripts() {
	wp_enqueue_script(
		'gwafabtabulous',
		WP_PLUGIN_URL.'/gwa-db-editor/js/fabtabulous.js'
	);
	wp_enqueue_script(
		'gwaprototype',
		WP_PLUGIN_URL.'/gwa-db-editor/js/prototype.js'
	);
	wp_enqueue_script(
		'gwaprototype',
		WP_PLUGIN_URL.'/gwa-db-editor/js/tablekit.js'
	);
	echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/gwa-db-editor/css/style.css" />' . "\n";
}

add_action('admin_menu', 'db_gwa_print_scripts',1);
add_action('admin_menu','modify_db_gwa_menu');

?>