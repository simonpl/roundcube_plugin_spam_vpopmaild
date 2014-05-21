<?php
/**
 * Spam vpopmaild
 *
 * Roundcube plugin to toggle the spam settings for a user via vpopmaild
 *
 * @version 1.0
 * @author Simon Plasger
 * @url https://github.com/simonpl/roundcube_plugin_spam_vpopmaild
 * @license GPLv3+
 */
class spam_vpopmaild extends rcube_plugin
{
    public $task    = 'settings';
    public $noframe = true;
    public $noajax  = true;

    function init()
    {
        $rcmail = rcmail::get_instance();
        $this->load_config();
        
        // Tab in settings
        $this->add_hook('settings_actions', array($this, 'settings_tab'));
	
	    $this->register_action('plugin.spam_vpopmaild', array($this, 'spam_vpopmaild_init'));
        $this->register_action('plugin.spam_vpopmaild-save', array($this, 'spam_vpopmaild_save'));
    }
    
    function settings_tab($args)
    {
        $this->add_texts('localization/');
        $args['actions'][] = array('action' => 'plugin.spam_vpopmaild', 'type' => 'link', 'class' => 'spam_vpopmaild', 'label' => 'spam_vpopmaild.spam_vpopmaild', 'title' => 'spam_vpopmaild.spam_vpopmaild');
        return $args;
    }
    
    function spam_vpopmaild_init()
    {
        $this->add_texts('localization/');
        $this->register_handler('plugin.body', array($this, 'spam_vpopmaild_form'));
        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle($this->gettext('spam_on_off'));
        $rcmail->output->send('plugin');
    }
    
    function spam_vpopmaild_save()
    {
        $rcmail = rcmail::get_instance();
        $this->add_texts('localization/');
        
        $this->register_handler('plugin.body', array($this, 'spam_vpopmaild_form'));
        $rcmail->output->set_pagetitle($this->gettext('spam_on_off'));
        if(isset($_POST['spamassassin']))
        {
            $no_spamassassin = 0;
        }
        else
        {
            $no_spamassassin = 1;
        }
        if(isset($_POST['delete_spam']))
        {
            $delete_spam = 1;
        }
        else
        {
            $delete_spam = 0;
        }
        $result = $this->set_state($rcmail->decrypt($_SESSION['password']), $no_spamassassin, $delete_spam);
        if($result)
            $rcmail->output->command('display_message', $this->gettext('success'), 'confirmation');
        else
            $rcmail->output->command('display_message', $this->gettext('save_error'), 'error');
            
        $rcmail->overwrite_action('plugin.spam_vpopmaild');
        $rcmail->output->send('plugin');
    }
    
    function load_state($pass)
    {
        $rcmail = rcmail::get_instance();
        
        $vpopmaild = new Net_Socket();
        
        // Connect to server
        if (PEAR::isError($vpopmaild->connect($rcmail->config->get('spam_vpopmaild_host'),
            $rcmail->config->get('spam_vpopmaild_port'), null))) {
            return false;
        }
        
        $vpopmaild->setTimeout($rcmail->config->get('password_vpopmaild_timeout'),0);
        
        $result = $vpopmaild->readLine();
        if(!preg_match('/^\+OK/', $result)) {
            $vpopmaild->disconnect();
            return false;
        }
        
        // login
        $vpopmaild->writeLine("login ". $_SESSION['username'] . " " . $pass);
        
        $result = $vpopmaild->readLine();

        if(!preg_match('/^\+OK+/', $result) ) {
            $vpopmaild->writeLine("quit");
            $vpopmaild->disconnect();
            return false;
        }
        $return = array();
        
        // fetch results
        do
        {
            $result = $vpopmaild->readLine();
            if(preg_match('/^no_spamassassin 0/', $result))
            {
                $return['no_spamassassin'] = 0;
            }
            else if(preg_match('/^no_spamassassin 1/', $result))
            {
                $return['no_spamassassin'] = 1;
            }
            else if(preg_match('/^delete_spam 0/', $result))
            {
                $return['delete_spam'] = 0;
            }
            else if(preg_match('/^delete_spam 1/', $result))
            {
                $return['delete_spam'] = 1;
            }
        }
        while(!preg_match('/^\./', $result));
        $vpopmaild->writeLine("quit");
        $vpopmaild->disconnect();
        return $return;
    }
    
    function set_state($pass, $no_spamassassin, $delete_spam)
    {
        $rcmail = rcmail::get_instance();
        
        $vpopmaild = new Net_Socket();
        
        if (PEAR::isError($vpopmaild->connect($rcmail->config->get('spam_vpopmaild_host'),
            $rcmail->config->get('spam_vpopmaild_port'), null))) {
            return false;
        }
        
        $vpopmaild->setTimeout($rcmail->config->get('password_vpopmaild_timeout'),0);
        
        $result = $vpopmaild->readLine();
        if(!preg_match('/^\+OK/', $result)) {
            $vpopmaild->disconnect();
            return false;
        }
        
        // slogin = silent login, don't show all user infos after login
        $vpopmaild->writeLine("slogin ". $_SESSION['username'] . " " . $pass);
        
        $result = $vpopmaild->readLine();

        if(!preg_match('/^\+OK+/', $result) ) {
            $vpopmaild->writeLine("quit");
            $vpopmaild->disconnect();
            return false;
        }
        
        $vpopmaild->writeLine("mod_user ". $_SESSION['username']);
        $vpopmaild->writeLine("no_spamassassin " . $no_spamassassin);
        $vpopmaild->writeLine("delete_spam " . $delete_spam);
        $vpopmaild->writeLine(".");
        $vpopmaild->writeLine("quit");
        $vpopmaild->disconnect();
        return true;
    }
       
    function spam_vpopmaild_form()
    {
        $rcmail = rcmail::get_instance();
        
        $state = $this->load_state($rcmail->decrypt($_SESSION['password']));
        
        if(!is_array($state))
        {
            $state = array('no_spamassassin' => 1, 'delete_spam' => 0);
            $rcmail->output->command('display_message', $this->gettext('load_error'), 'error');
        }
            
        $rcmail->output->set_env('product_name', $rcmail->config->get('product_name'));
        
        $table = new html_table(array('cols' => 2));
        $field_id = 'spam_vpopmaild_detect';
        $input_togglespam = new html_checkbox(array('name' => 'spamassassin', 'id' => $field_id, 'value' => 'spamassassin'));
        $table->add('title', html::label($field_id, $this->gettext('detection')));
        $table->add(null, $input_togglespam->show($state['no_spamassassin'] == 1 ? '' : 'spamassassin'));
        $field_id = 'spam_vpopmaild_delete';
        $input_togglespam = new html_checkbox(array('name' => 'delete_spam', 'id' => $field_id, 'value' => 'delete_spam'));
        $table->add('title', html::label($field_id, $this->gettext('delete')));
        $table->add(null, $input_togglespam->show($state['delete_spam'] == 0 ? '' : 'delete_spam'));
        $submit = new html_inputfield(array('type' => 'submit', 'id' => 'spam_vpopmaild_save', 'class' => 'button mainaction', 'value' => $this->gettext('save')));
        
        $out = html::div(array('class' => 'box'), 
               html::div(array('id' => 'prefs-title', 'class' => 'boxtitle'), $this->gettext('spam_on_off')) .
               html::div(array('class' => 'boxcontent'), $table->show() .
               html::p('formbuttons', $submit->show()
               )));
                
       $rcmail->output->add_gui_object('spamform', 'spam_vpopmaild-form');
       
       return $rcmail->output->form_tag(array(
           'id' => 'spam_vpopmaild-form',
           'name' => 'spam_vpopmaild-form',
           'method' => 'post',
           'action' => './?_task=settings&_action=plugin.spam_vpopmaild-save',
       ), $out);
    } 
}
