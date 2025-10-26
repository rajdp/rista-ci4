<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sitecontent_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function bloggerDetails($condition)
    {
        $query = $this->db->query("SELECT b.content_id,b.name,b.name_slug,b.image,b.short_description,b.long_description,b.status,b.category_id,b.subject_id,b.views,b.author,
                                   (CASE WHEN b.display_from = '0000-00-00 00:00:00' THEN ''
                                   ELSE b.display_from END) as display_from,
                                   (CASE WHEN b.display_until = '0000-00-00 00:00:00' THEN ''
                                   ELSE b.display_until END) as display_until,b.display_order,b.created_date,b.redirect_url,b.location,b.timing,b.event_date as eventDate,
                                   (SELECT category_name FROM tbl_content_category WHERE category_id = b.category_id) as category_name
                                   FROM tbl_content as b $condition")->result_array();
        return $query;
    }
    public function getBlogSeo($condition)
    {
        $query = $this->db->query("SELECT s.seo_id,s.content_id,c.name,s.meta_author,s.meta_title,s.meta_description,s.meta_keywords,
                                   s.meta_keyphrase,s.meta_topic,s.meta_subject,s.meta_classification,s.meta_robots,s.meta_rating,s.meta_audience,
                                   s.og_title,s.og_type,s.og_site_name,s.og_description,s.og_site_url,s.twitter_title,
                                   s.twitter_site,s.twitter_card,s.twitter_description,s.twitter_creator,s.status 
                                   FROM tbl_content_seo as s
                                   LEFT JOIN tbl_content as c ON c.content_id = s.content_id
                                   $condition")->result_array();
        return $query;
    }

    public function categoryList($condition)
    {
        $query = $this->db->query("SELECT bc.category_id, bc.category_name, bc.status,COALESCE(bc.description,'') as description,bc.path,bc.display_order,
                                  (SELECT COUNT(content_id) FROM tbl_content WHERE category_id = bc.category_id ) as content_count 
                                   FROM tbl_content_category as bc $condition ")->result_array();
        return $query;
    }
}
