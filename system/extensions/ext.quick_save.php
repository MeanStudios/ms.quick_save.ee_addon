<?php  if ( ! defined('EXT')) exit('No direct script access allowed');
/**
 * jQuery Quick Save
 *
 * An ExpressionEngine Extension that allows the ability to use Ctrl+s to
 * quickly save a template.  Use Ctrl+Shift+s to trigger the Update and Finished
 * function of the template.
 * This extension also adds an Auto-Save feature to your templates that is defaulted to 20 seconds.
 * This extension requires you to have the jQuery for the Control Panel enabled.
 * DO NOT FORGET to set the path to the JS files included in this download.
 *
 * @package		ExpressionEngine
 * @author		Cody Lundquist
 * @copyright	Copyright (c) 2009, Cody Lundquist.
 * @license		http://creativecommons.org/licenses/by-sa/3.0/
 * @link		http://github.com/MeanStudios/ms.quick_save.ee_addon/
 * @filesource
 *
 * This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
 * To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
 * or send a letter to Creative Commons, 171 Second Street, Suite 300,
 * San Francisco, California, 94105, USA. 
 *
 */
class Quick_save {

	var $settings		= array();
	var $name			= 'jQuery Quick Save';
    var $class_name     = 'Quick_save';
	var $version		= '2.0.3';
	var $description	= 'Adds ability to use Ctrl+S to quickly save your template.  It also adds an Auto-Update feature as well.';
	var $settings_exist	= 'y';
	var $docs_url		= 'http://wiki.github.com/MeanStudios/ms.quick_save.ee_addon';

	/**
	 * Constructor
	 */
	function Quick_save($settings=array())
	{
		$this->settings = $this->get_site_settings($settings);
	}

    function get_all_settings()
	{
		global $DB;

		$query = $DB->query("SELECT settings
		                     FROM exp_extensions
		                     WHERE class = '{$this->class_name}'
		                     AND settings != ''
		                     LIMIT 1");

		return $query->num_rows
			? unserialize($query->row['settings'])
			: array();
	}

    function get_default_settings()
	{
		$settings = array(
			'js_url'     => 'http://example.com/system/extensions/js/',
			'seconds_to_save'   => '120',
		);

		return $settings;
	}

    function get_site_settings($settings=array())
	{
		global $PREFS;

		$site_settings = $this->get_default_settings();

		$site_id = $PREFS->ini('site_id');
		if (isset($settings[$site_id]))
		{
			$site_settings = array_merge($site_settings, $settings[$site_id]);
		}

		return $site_settings;
	}

    function settings_form($current)
	{
	    $current = $this->get_site_settings($current);

	    global $DB, $DSP, $LANG, $IN, $PREFS;

		// Breadcrumbs

		$DSP->crumbline = TRUE;

		$DSP->title = $LANG->line('extension_settings');
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities'))
		            . $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')))
		            . $DSP->crumb_item($this->name);

	    $DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name='.$IN->GBL('name'));

		$DSP->body = '';

	    $DSP->body .= "<h1>{$this->name} <small>{$this->version}</small></h1>"

		           . $DSP->form_open(
		                                 array(
		                                     'action' => 'C=admin'.AMP.'M=utilities'.AMP.'P=save_extension_settings',
		                                     'name'   => 'settings_example',
		                                     'id'     => 'settings_example'
		                                 ),
		                                 array(
		                                     'name' => strtolower($this->class_name)
		                                 )
		                             )

		           . $DSP->table_open(
		                                  array(
		                                      'class'  => 'tableBorder',
		                                      'border' => '0',
		                                      'style'  => 'margin-top:18px; width:100%'
		                                  )
		                              )

		           . $DSP->tr()
		           . $DSP->td('tableHeading', '', '2')
		           . $LANG->line('settings')
		           . $DSP->td_c()
		           . $DSP->tr_c()

		             // URL to JS Files
		           . $DSP->tr()
		           . '<td class="tableCellOne" style="width:60%; padding-top:8px; vertical-align:top;">'
		           . $DSP->qdiv('defaultBold', $LANG->line('js_url'))
		           . $DSP->td_c()
		           . $DSP->td('tableCellOne')
		           . $DSP->input_text('js_url', $current['js_url'])
		           . $DSP->td_c()
		           . $DSP->tr_c()

		             // Timeout till auto-save.
		           . $DSP->tr()
		           . '<td class="tableCellOne" style="width:60%; padding-top:8px; vertical-align:top;">'
		           . $DSP->qdiv('defaultBold', $LANG->line('seconds_to_save'))
		           . $DSP->td_c()
		           . $DSP->td('tableCellOne')
		           . $DSP->input_text('seconds_to_save', $current['seconds_to_save'])
		           . $DSP->td_c()
		           . $DSP->tr_c()
		           . $DSP->table_c()
                   . $DSP->qdiv('itemWrapperTop', $DSP->input_submit())
		           . $DSP->form_c();
	}

    function save_settings()
	{
		global $DB, $PREFS;

		$settings = $this->get_all_settings();
		$current = $this->get_site_settings($settings);

		// Save new settings
		$settings[$PREFS->ini('site_id')] =
			$this->settings = array(
				'js_url'            => $_POST['js_url'],
				'seconds_to_save'   => $_POST['seconds_to_save'],
			);

		$DB->query("UPDATE exp_extensions
		            SET settings = '".addslashes(serialize($settings))."'
		            WHERE class = '{$this->class_name}'");
	}

    function activate_extension($settings='')
	{
		global $DB;

		// Get settings
		if ( ! (is_array($settings) AND $settings))
		{
			$settings = $this->get_all_settings();
		}

		// Delete old hooks
		$DB->query("DELETE FROM exp_extensions
		            WHERE class = '{$this->class_name}'");

		// Add new extensions
		$ext_template = array(
			'class'    => $this->class_name,
			'settings' => addslashes(serialize($settings)),
			'priority' => 10,
			'version'  => $this->version,
			'enabled'  => 'y'
		);

		$extensions = array(

			array('hook'=>'show_full_control_panel_end', 'method'=>'show_full_control_panel_end'),
			array('hook'=>'edit_template_start',  'method'=>'edit_template_start'),
            array('hook'=>'update_template_end', 'method'=>'update_template_end')
		);

		foreach($extensions as $extension)
		{
			$ext = array_merge($ext_template, $extension);
			$DB->query($DB->insert_string('exp_extensions', $ext));
		}
	}

	function update_extension($current='')
	{
		global $DB;

		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

		if ($current < '2.0.3')
		{
			// make settings site-specific
			$settings = $this->get_all_settings();
			$all_settings = array();
			$sites = $DB->query('SELECT site_id FROM exp_sites');
			foreach($sites->result as $site)
			{
				$all_settings[$site['site_id']] = $settings;
			}

			// Add new hooks
			$this->activate_extension($all_settings);
			return;
		}

		// update the version
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '{$this->class_name}'");
	}

	// --------------------------------------------------------------------

	function disable_extension()
	{
		global $DB;

		$DB->query("UPDATE exp_extensions
		            SET enabled='n'
		            WHERE class='{$this->class_name}'");
	}

	function show_full_control_panel_end($html)
	{
		global $EXT;

		$html = ($EXT->last_call !== FALSE) ? $EXT->last_call : $html;

		$find = '<title>';

		$replace = '<script type="text/javascript" src="'.$this->settings['js_url'].'jquery.hotkeys.js"></script>'."\n";
        $replace .= '<script type="text/javascript" src="'.$this->settings['js_url'].'jquery.ajaxform.js"></script>'."\n";

        ob_start();
        ?>
<script type="text/javascript">
$(document).ready(function() {
   hotkeys();
   autosave();
});
</script>
        <?php
        $replace .= ob_get_contents() . "\n";
        ob_end_clean();

		$replace .= "<title>";
		$html = str_replace($find, $replace, $html);

		return $html;
	}


    function edit_template_start($query, $template_id, $message) {
    
        global $DSP, $IN, $DB, $EXT, $PREFS, $SESS, $FNS, $LOC, $LANG;
                
        if ($template_id == '')
        {
            if ( ! $template_id = $IN->GBL('id'))
            {
                return false;
            }
        }
        
        if ( ! is_numeric($template_id))
        {
        	return false;
        }
        
        $user_blog = ($SESS->userdata['tmpl_group_id'] == 0) ? FALSE : TRUE;
        
        $query = $DB->query("SELECT group_id, template_name, save_template_file, template_data, template_notes, template_type, edit_date, last_author_id FROM exp_templates WHERE template_id = '$template_id'");
        
        $group_id = $query->row['group_id'];
        $template_type = $query->row['template_type'];
        
        $result = $DB->query("SELECT group_name FROM exp_template_groups WHERE group_id = '".$group_id."'");
                                               
        $template_group  = $result->row['group_name']; 
                        
        if ( ! $this->template_access_privs(array('group_id' => $group_id)))
        {
        	return $DSP->no_access_message();
        }
        
        $template_data  	= $query->row['template_data'];   
        $template_name  	= $query->row['template_name']; 
        $template_notes 	= $query->row['template_notes']; 
        $save_template_file	= $query->row['save_template_file']; 
        
		    
		if ($PREFS->ini('time_format') == 'us')
		{
			$datestr = '%m/%d/%y %h:%i %a';
		}
		else
		{
			$datestr = '%Y-%m-%d %H:%i';
		}

		$edit_date = $LOC->decode_date($datestr, $query->row['edit_date'], TRUE);
		
		$mquery = $DB->query("SELECT screen_name FROM exp_members WHERE member_id = ".$query->row['last_author_id']);

		if ($mquery->num_rows == 0)
		{
			// this feature was added in 1.6.5, so existing templates following that update will have a member_id of '0'
			// and will not have a known value until the template is edited again.
			$last_author = '';
		}
		else
		{
			$last_author = $mquery->row['screen_name'];
		}
        // Clear old revisions


        if ($PREFS->ini('save_tmpl_revisions') == 'y')
        {
			$maxrev = $PREFS->ini('max_tmpl_revisions');

			if ($maxrev != '' AND is_numeric($maxrev) AND $maxrev > 0)
			{
				$res = $DB->query("SELECT tracker_id FROM exp_revision_tracker WHERE item_id = '$template_id' AND item_table = 'exp_templates' AND item_field ='template_data' ORDER BY tracker_id DESC");

				if ($res->num_rows > 0  AND $res->num_rows > $maxrev)
				{
					$flag = '';

					$ct = 1;
					foreach ($res->result as $row)
					{
						if ($ct >= $maxrev)
						{
							$flag = $row['tracker_id'];
							break;
						}

						$ct++;
					}

					if ($flag != '')
					{
						$DB->query("DELETE FROM exp_revision_tracker WHERE tracker_id < $flag AND item_id = '".$DB->escape_str($template_id)."' AND item_table = 'exp_templates' AND item_field ='template_data'");
					}
				}
			}
        }


        if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '' AND $save_template_file == 'y')
        {
			$basepath = $PREFS->ini('tmpl_file_basepath');

			if ( ! ereg("/$", $basepath)) $basepath .= '/';

			$basepath .= $template_group.'/'.$template_name.'.php';

			if ($file = $DSP->file_open($basepath))
			{
				$template_data = $file;
			}
        }

		$qs = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
		$sitepath = $FNS->fetch_site_index(0, 0).$qs.'URL='.$FNS->fetch_site_index();

        if ( ! ereg("/$", $sitepath))
            $sitepath .= '/';

        if ($template_type == 'css')
        {
        	$sitepath = substr($sitepath, 0, -1);
        	$sitepath .= $qs.'css='.$template_group.'/'.$template_name.'/';
        }
        else
        {
        	$sitepath .= $template_group.(($template_name == 'index') ? '/' : '/'.$template_name.'/');
    	}

        $DSP->title  = $LANG->line('edit_template').' | '.$template_name;
        $DSP->crumb  = $LANG->line('edit_template');
		$DSP->right_crumb($LANG->line('view_rendered_template'), $sitepath, '', TRUE);

        ob_start();

        ?>
        <script type="text/javascript">
        <!--

            function viewRevision()
            {
                var id = document.forms.revisions.revision_history.value;

                if (id == "")
                {
                    return false;
                }
                else if (id == "clear")
                {
                    var items = document.forms.revisions.revision_history;

                    for (i = items.length -1; i >= 1; i--)
                    {
                        items.options[i] = null;
                    }

                    document.forms.revisions.revision_history.options[0].selected = true;

                    flipButtonText(1);

                    window.open ("<?php echo BASE.'&C=templates&M=clear_revisions&id='.$template_id.'&Z=1'; ?>" ,"Revision", "width=500, height=260, location=0, menubar=0, resizable=0, scrollbars=0, status=0, titlebar=0, toolbar=0, screenX=60, left=60, screenY=60, top=60");

                    return false;
                }
                else
                {
                    window.open ("<?php echo BASE.'&C=templates&M=revision_history&Z=1'; ?>&id="+id ,"Revision");

                    return false;
                }
                return false;
            }

            function flipButtonText(which)
            {
                if (which == "clear")
                {
                    document.forms.revisions.submit.value = '<?php echo $LANG->line('clear'); ?>';
                }
                else
                {
                    document.forms.revisions.submit.value = '<?php echo $LANG->line('view'); ?>';
                }
            }

        function showhide_notes()
        {
			if (document.getElementById('notes').style.display == "block")
			{
				document.getElementById('notes').style.display = "none";
				document.getElementById('noteslink').style.display = "block";
        	}
        	else
        	{
				document.getElementById('notes').style.display = "block";
				document.getElementById('noteslink').style.display = "none";
        	}
        }

        //-->
        </script>

        <?php

        $buffer = ob_get_contents();

        ob_end_clean();

        $r  = $buffer;

        $r .= $DSP->form_open(
        						array(
        								'action'	=> '',
        								'name'		=> 'revisions',
        								'id'		=> 'revisions'
        							),

        						array(
        								'template_id' => $template_id
        							)
        					);


        $r .= $DSP->table('', '', '', '100%')
             .$DSP->tr()
             .$DSP->td('tableHeading')
             .$LANG->line('template_name').NBS.NBS.$template_group.'/'.$template_name.NBS.NBS
			 .'('.$LANG->line('last_edit').NBS.$edit_date
			 .(($last_author != '') ? NBS.$LANG->line('by').NBS.$last_author : '').')'
             .$DSP->td_c();

        $r .= $DSP->td('tableHeading')
             .$DSP->div('defaultRight');

        if ($user_blog == FALSE)
        {
             $r .= "<select name='revision_history' class='select' onchange='flipButtonText(this.options[this.selectedIndex].value);'>"
                 .NL
                 .$DSP->input_select_option('', $LANG->line('revision_history'));

            $query = $DB->query("SELECT tracker_id, item_date, screen_name FROM exp_revision_tracker LEFT JOIN exp_members ON exp_members.member_id = exp_revision_tracker.item_author_id WHERE item_table = 'exp_templates' AND item_field = 'template_data' AND item_id = '".$DB->escape_str($template_id)."' ORDER BY tracker_id DESC");

            if ($query->num_rows > 0)
            {
                foreach ($query->result as $row)
                {
                    $r .= $DSP->input_select_option($row['tracker_id'], $LOC->set_human_time($row['item_date']).' ('.$row['screen_name'].')');
                }

                $r .= $DSP->input_select_option('clear', $LANG->line('clear_revision_history'));
            }

            $r .= $DSP->input_select_footer()
                 .$DSP->input_submit($LANG->line('view'), 'submit', "onclick='return viewRevision();'");
        }
        else
        {
            $r .= NBS;
        }
        $r .=  $DSP->div_c()
              .$DSP->td_c()
              .$DSP->tr_c()
              .$DSP->table_c()
              .$DSP->form_close();

        $r .= $message;
        $r .= '<div id="auto_save" class="success"></div>';

        $r .= $DSP->form_open(array('action' => 'C=templates'.AMP.'M=update_template', 'id' => 'template_form'))
             .$DSP->input_hidden('template_id', $template_id);

        $r .= $DSP->qdiv('templatepad', $DSP->input_textarea('template_data', $template_data, $SESS->userdata['template_size'], 'textarea', '100%'));

        $notelink	= ' <a href="javascript:void(0);" onclick="showhide_notes();return false;"><b>'.$LANG->line('template_notes').'</b></a>';
		$expand		= '<img src="'.PATH_CP_IMG.'expand.gif" border="0"  width="10" height="10" alt="Expand" />';
		$collapse	= '<img src="'.PATH_CP_IMG.'collapse.gif" border="0"  width="10" height="10" alt="Collapse" />';

		$js = ' onclick="showhide_notes();return false;" onmouseover="navTabOn(\'noteopen\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'noteopen\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';

		$r .= '<div id="noteslink" style="display: block; padding:0; margin: 0;">';
		$r .= "<div class='tableHeadingAlt' id='noteopen' ".$js.">";
		$r .= $expand.' '.$LANG->line('template_notes');
		$r .= $DSP->div_c();
		$r .= $DSP->div_c();

		$js = ' onclick="showhide_notes();return false;" onmouseover="navTabOn(\'noteclose\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onmouseout="navTabOff(\'noteclose\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';

		$r .= '<div id="notes" style="display: none; padding:0; margin: 0;">';
		$r .= "<div class='tableHeadingAlt' id='noteclose' ".$js.">";
		$r .= $collapse.' '.$LANG->line('template_notes');
        $r .= $DSP->div_c();
		$r .= $DSP->div('templatebox');
		$r .= $DSP->qdiv('itemWrapper', $LANG->line('template_notes_desc'));
        $r .= $DSP->input_textarea('template_notes', $template_notes, '24', 'textarea', '100%');
        $r .= $DSP->div_c();
		$r .= $DSP->div_c();

		$r .= $DSP->div('templatebox');
        $r .= $DSP->table('', '', '6', '100%')
             .$DSP->tr()
             .$DSP->td('', '25%', '', '', 'top')
             .$DSP->div('bigPad');

        if ($user_blog == FALSE AND $PREFS->ini('save_tmpl_revisions') == 'y')
        {
              $selected = ($PREFS->ini('save_tmpl_revisions') == 'y') ? 1 : '';

             $r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('save_history', 'y', $selected).NBS.NBS.$LANG->line('save_history'));
        }

        if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '')
        {
			$selected = ($save_template_file == 'y') ? 1 : '';

			$r .= $DSP->qdiv('itemWrapper', $DSP->input_checkbox('save_template_file', 'y', $selected).NBS.NBS.$LANG->line('save_template_file'));
		}


        $r .= $DSP->qdiv('itemWrapper', $DSP->input_submit($LANG->line('update'),$LANG->line('update'), "id=\"update_template\"").NBS.$DSP->input_submit($LANG->line('update_template_return'),'return', "id=\"update_template_return\""));


		$r .= $DSP->td_c()
             .$DSP->td('', '25%', '', '', 'top')
             .$DSP->div('bigPad')
			 .$DSP->qdiv('itemWrapper', $DSP->input_text('columns', $SESS->userdata['template_size'], '4', '2', 'input', '30px').NBS.NBS.$LANG->line('template_size'))
             .$DSP->div_c()
			 .$DSP->td_c()
             .$DSP->tr_c()
             .$DSP->table_c()
             .$DSP->div_c();

		$r .= $DSP->form_close();

		// TEMPLATE EXPORT LINK
ob_start();
?>
		<script type="text/javascript">
        <!--

		function export_template()
		{
			document.forms['export_template_form'].export_data.value = document.getElementById('template_data').value;
			document.forms['export_template_form'].submit();
		}
        prev_template_data = $('#template_data').val();
        function hotkeys(){
            update = $('#update_template');
            update_finish = $('#update_template_return');
            $(document).bind('keydown', 'Ctrl+s',function (evt){update.click(); return false; });
            $(document).bind('keydown', 'Ctrl+Shift+s',function (evt){update_finish.click(); return false; });
        };
        function autosave()
        {
            t = setTimeout("autosave()", <?php echo (1000 * $this->settings['seconds_to_save']); ?>);
            template_form = $('#template_form');
            form_url = template_form.attr('action');
            template_data = $('#template_data').val();
            var options = {
                target:        '#auto_save',   // target element(s) to be updated with server response
                url: form_url + '&auto_save=true'
            };
            if (prev_template_data != template_data)
            {
                template_form.ajaxSubmit(options);
                prev_template_data = template_data;
            }
        }
		//-->
        </script>
<?php
        $buffer = ob_get_contents();
        ob_end_clean();

        $r .= $buffer;

		$r .= $DSP->form_open(array('action' => 'C=templates'.AMP.'M=export_template'.AMP.'id='.$group_id.AMP.'tid='.$template_id,
									'id'	 => "export_template_form"),
							  array('export_data' => ''));
		$r .= $DSP->qdiv('itemWrapper', $DSP->anchor('javascript:nullo();', $LANG->line('export_template'), 'onclick="export_template();return false;"'));
		$r .= $DSP->form_close();

		/* -------------------------------------
		/*  'edit_template_end' hook.
		/*  - Allows content to be added to the output
		/*  - Added 1.6.0
		*/
			if ($EXT->active_hook('edit_template_end') === TRUE)
			{
				$r .= $EXT->call_extension('edit_template_end', $query, $template_id);
				if ($EXT->end_script === TRUE) return;
			}
		/*
		/* -------------------------------------*/

        $DSP->body = $r;

        $EXT->end_script = TRUE;
    }

    /** -----------------------------
    /**  Verify access privileges
    /** -----------------------------*/

    function template_access_privs($data = '')
    {
    	global $SESS, $DB;

    	// If the user is a Super Admin, return true

		if ($SESS->userdata['group_id'] == 1)
		{
    		return TRUE;
		}

    	$template_id = '';
    	$group_id	 = '';

    	if (is_array($data))
    	{
    		if (isset($data['template_id']))
    		{
    			$template_id = $data['template_id'];
    		}

    		if (isset($data['group_id']))
    		{
    			$group_id = $data['group_id'];
    		}
    	}


        if ($group_id == '')
        {
        	if ($template_id == '')
        	{
        		return FALSE;
        	}
        	else
        	{
           		$query = $DB->query("SELECT group_id, template_name FROM exp_templates WHERE template_id = '".$DB->escape_str($template_id)."'");

           		$group_id = $query->row['group_id'];
            }
        }


        if ($SESS->userdata['tmpl_group_id'] == 0)
        {
			$access = FALSE;

			foreach ($SESS->userdata['assigned_template_groups'] as $key => $val)
			{
				if ($group_id == $key)
				{
					$access = TRUE;
					break;
				}
			}

			if ($access == FALSE)
			{
				return FALSE;
			}
        }
        else
        {
			if ($group_id != $SESS->userdata['tmpl_group_id'] )
			{
				return FALSE;
			}
        }

		return TRUE;
    }

    function update_template_end($template_id, $message) {
        global $LOC, $IN, $LANG;
        if ($IN->GBL('auto_save')=="true")
        {
        	echo "Templated Updated at: " . date("h:i:s a", $LOC->set_localized_time());
            exit;
        }
    }
    /* END */

	// --------------------------------------------------------------------

}
// END CLASS Quick_save

/* End of file ext.quick_save.php */
/* Location: ./system/extensions/ext.quick_save.php */