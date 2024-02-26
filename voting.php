<?php
/**
 * Plugin Name: Article voting
 * Description: plugin that allows users to vote on articles.
 * Version: 1.0
 * Author: A'laa Albuser
 */
defined('ABSPATH') or die('No script kiddies please!');

class Voting_Articale {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'append_voting_buttons'));
        add_action('wp_ajax_submit_vote', array($this, 'submit_vote'));
        add_action('wp_ajax_nopriv_submit_vote', array($this, 'submit_vote'));
        add_action('add_meta_boxes', array($this, 'add_voting_results_meta_box'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wah-style', plugins_url('css/style.css', __FILE__));
        wp_enqueue_script('wah-script', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);

        wp_localize_script('wah-script', 'wah_ajax_obj', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wah-nonce')
        ));
    }

    public function append_voting_buttons($content) {
        global $post;
        if (is_single() && in_the_loop() && is_main_query()) {
            $content .= $this->get_voting_buttons_html($post->ID); 
        }
        return $content;
    }
    
    private function get_voting_buttons_html($post_id) {
        // Retrieve current vote counts from post meta
        $yes_votes = get_post_meta($post_id, 'yes_votes', true) ?: 0;
        $no_votes = get_post_meta($post_id, 'no_votes', true) ?: 0;
        $total_votes = $yes_votes + $no_votes;
        $yes_percentage = $total_votes > 0 ? round(($yes_votes / $total_votes) * 100) : 0;
        $no_percentage = 100 - $yes_percentage;
    
        // Get the URL for the images
        $happy_face_url = plugins_url('images/happy-face.png', __FILE__);
        $sad_face_url = plugins_url('images/sad-face.png', __FILE__);
    
        $html = '
        <div id="wah-voting">
            <div class="voting-buttons">
                <p>WAS THIS ARTICLE HELPFUL?</p>
                <button id="wah-yes" class="vote-button" data-vote="yes" data-postid="' . esc_attr($post_id) . '">
                    <span class="">Yes</span>
                    <img src="' . esc_url($happy_face_url) . '" alt="Happy face"/>
                </button>
                <button id="wah-no" class="vote-button" data-vote="no" data-postid="' . esc_attr($post_id) . '">
                    <span class="">No</span>
                    <img src="' . esc_url($sad_face_url) . '" alt="Sad face"/>
                </button>
            </div>
            <div id="wah-feedback" style="display:none;">
                <p>THANK YOU FOR YOUR FEEDBACK.</p>
                <div class="wah-results">
                    <span class="wah-result wah-yes">
                        <img src="' . esc_url($happy_face_url) . '" alt="Happy face" />
                        <span class="wah-percentage">' . esc_html($yes_percentage) . '%</span>
                    </span>
                    <span class="wah-result wah-no">
                        <img src="' . esc_url($sad_face_url) . '" alt="Sad face" />
                        <span class="wah-percentage">' . esc_html($no_percentage) . '%</span>
                    </span>
                </div>
            </div>
        </div>';
        return $html;
    }


    public function submit_vote() {
    check_ajax_referer('wah-nonce', 'nonce');

    $post_id = intval($_POST['post_id']);
    $vote = $_POST['vote']; // 'yes' or 'no'
    $user_ip = $_SERVER['REMOTE_ADDR']; // Get the user's IP address

    // Check if this IP has already voted on this post
    $has_voted_key = "has_voted_{$post_id}_{$user_ip}";
    $has_voted = get_post_meta($post_id, $has_voted_key, true);
    if (!empty($has_voted)) {
        wp_send_json_error('You have already voted.');
        return; 
    }
    if (!in_array($vote, ['yes', 'no'])) {
        wp_send_json_error('Invalid vote.');
    }
    
    // Update the vote counts
    $vote_count = (int) get_post_meta($post_id, $vote . '_votes', true);
    update_post_meta($post_id, $vote . '_votes', ++$vote_count);

    // Calculate new percentages
    $yes_votes = (int) get_post_meta($post_id, 'yes_votes', true);
    $no_votes = (int) get_post_meta($post_id, 'no_votes', true);
    $total_votes = $yes_votes + $no_votes;
    $yes_percentage = $total_votes > 0 ? round(($yes_votes / $total_votes) * 100) : 0;
    $no_percentage = 100 - $yes_percentage;

    wp_send_json_success([
        'yes_percentage' => $yes_percentage,
        'no_percentage' => $no_percentage
    ]);
    }
    public function add_voting_results_meta_box() {
        add_meta_box(
            'wah-voting-results',            
            'Voting Results',                
            array($this, 'voting_results_meta_box_html'), 
            'post',                          
            'side'                         
        );
    }

    public function voting_results_meta_box_html($post) {
        // Retrieve current vote counts from post meta
        $yes_votes = get_post_meta($post->ID, 'yes_votes', true) ?: '0';
        $no_votes = get_post_meta($post->ID, 'no_votes', true) ?: '0';
    
        // Display the voting results
        echo '<p>Yes Votes: ' . esc_html($yes_votes) . '</p>';
        echo '<p>No Votes: ' . esc_html($no_votes) . '</p>';
    }
}

new Voting_Articale();