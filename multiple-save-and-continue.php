<?php

/**
 * Gravity Forms // Multiple Save and Continue
 *
 * Utilizes the Save an Continue feature to hold several ongoing forms simultaneously
 * Credit to Gravity Wiz for basic code structure http://gravitywiz.com/simple-submission-approval-gravity-forms/
 *
 * @version   1.0
 * @author    Ryan Prejean <ryan@gcit.net>
 * @license   GPL-2.0+
 * @link      http://www.ryanprejean.com/
 */
class Multi_Save_Continue {

    protected static $is_script_output = false;

    public function __construct( $args = array() ) {

        // set our default arguments, parse against the provided arguments, and store for use throughout the class
        $this->_args = wp_parse_args( $args, array(
            'form_id'              => false,
            'field_id'             => false,
            'choices'              => false,
            'button_class'         => false
        ) );

        // do version check in the init to make sure if GF is going to be loaded, it is already loaded
        add_action( 'init', array( $this, 'init' ) );

    }

    function init() {

        // make sure we're running the required minimum version of Gravity Forms
        if( ! property_exists( 'GFCommon', 'version' ) || ! version_compare( GFCommon::$version, '1.9', '>=' ) ) {
            return;
        }

        // time for hooks
        add_filter( 'gform_register_init_scripts',        array( $this, 'add_init_script' ) );
        add_filter( 'gform_save_and_continue_resume_url', array( $this, 'add_approval_query_arg' ), 10, 4 );
        
    }

    function add_init_script( $form ) {

        if( ! $this->is_applicable_form( $form ) ) {
            return;
        }

        $numbers = array("Zero","One","Two","Three","Four","Five","Six","Seven","Eight","Nine");

        
        for ($i = 1; $i <= $this->_args['choices']; $i++) {
            $output .= "<button id=\"btn{$i}\" class=\"gform_button button {$this->_args['button_class']}\">{$numbers[$i]}</button>";
        }

        $script = '
            ( function( $ ) {
                var $saveContinueButton = $( "#gform_save_" + formId + "_link" ),
                    label = $saveContinueButton.html(),
                    field_id = "_' . $this->_args['field_id'] . '";
                
                $saveContinueButton.hide();

                var html = $( "<div />" ).append( $saveContinueButton.clone() ).html();

                var output = "' . addslashes($output) . '";

                var buttons = "<div style=\"float:right;\"> \
                    <p style=\"display:inline-block;padding-right:10px;\">" + label + ": </p> \
                    ' . addslashes($output) . ' \
                    </div>";

                $saveContinueButton.replaceWith( html.replace( "</a>", "</a>" + buttons ) );
                $(".' . $this->_args['button_class'] . '").click(function(){
                    var selected = $(this).html();
                    $( "#input_" + formId + field_id ).val(selected);
                    $saveContinueButton.click();
                });

            } )( jQuery );';

        $slug = 'multi_save_and_continue';

        GFFormDisplay::add_init_script( $this->_args['form_id'], $slug, GFFormDisplay::ON_PAGE_RENDER, $script );

    }

    function is_applicable_form( $form ) {

        $form_id = isset( $form['id'] ) ? $form['id'] : $form;

        return $form_id == $this->_args['form_id'];
    }

    function add_approval_query_arg( $resume_url, $form, $token, $email ) {

        if( ! $this->is_applicable_form( $form ) ) {
            return $resume_url;
        }

        $input = "input_" . $this->_args['field_id'];
        $save_name = "gfsave_" . $this->_args['form_id'] . "_" . rgpost( $input );
        $user_id = get_current_user_id();	
        update_user_meta( $user_id, $save_name , $resume_url );
        
        return $resume_url;
    }

}

# Configuration

new Multi_Save_Continue( array(
    'form_id'              => 1,
    'field_id'             => 119,
    'choices'              => 3,
    'button_class'         => "jsasave"
) );