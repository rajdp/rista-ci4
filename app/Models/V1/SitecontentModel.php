<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class SitecontentModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'tbl_content';
    protected $allowedFields = [
        'content_id',
        'name',
        'name_slug',
        'image',
        'short_description',
        'long_description',
        'status',
        'category_id',
        'subject_id',
        'views',
        'author',
        'display_from',
        'display_until',
        'display_order',
        'created_date',
        'redirect_url',
        'location',
        'timing',
        'event_date'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getBloggerDetails(string $condition): array
    {
        $builder = $this->getBuilder('tbl_content b');
        $builder->select('b.content_id, b.name, b.name_slug, b.image, b.short_description, b.long_description, 
            b.status, b.category_id, b.subject_id, b.views, b.author,
            CASE WHEN b.display_from = "0000-00-00 00:00:00" THEN "" ELSE b.display_from END as display_from,
            CASE WHEN b.display_until = "0000-00-00 00:00:00" THEN "" ELSE b.display_until END as display_until,
            b.display_order, b.created_date, b.redirect_url, b.location, b.timing, b.event_date as eventDate,
            (SELECT category_name FROM tbl_content_category WHERE category_id = b.category_id) as category_name');
        
        if ($condition) {
            $builder->where($condition);
        }
        
        return $this->getResult($builder);
    }

    public function getBlogSeo(string $condition): array
    {
        $builder = $this->getBuilder('tbl_content_seo s');
        $builder->select('s.seo_id, s.content_id, c.name, s.meta_author, s.meta_title, s.meta_description, 
            s.meta_keywords, s.meta_keyphrase, s.meta_topic, s.meta_subject, s.meta_classification, 
            s.meta_robots, s.meta_rating, s.meta_audience, s.og_title, s.og_type, s.og_site_name, 
            s.og_description, s.og_site_url, s.twitter_title, s.twitter_site, s.twitter_card, 
            s.twitter_description, s.twitter_creator, s.status');
        
        $builder->join('tbl_content c', 'c.content_id = s.content_id', 'left');
        
        if ($condition) {
            $builder->where($condition);
        }
        
        return $this->getResult($builder);
    }

    public function getCategoryList(string $condition): array
    {
        $builder = $this->getBuilder('tbl_content_category bc');
        $builder->select('bc.category_id, bc.category_name, bc.status, 
            COALESCE(bc.description, "") as description, bc.path, bc.display_order,
            (SELECT COUNT(content_id) FROM tbl_content WHERE category_id = bc.category_id) as content_count');
        
        if ($condition) {
            $builder->where($condition);
        }
        
        return $this->getResult($builder);
    }
} 