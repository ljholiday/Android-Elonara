<?php

namespace DgoraWcas\Engines\WordPressNative;

use  DgoraWcas\Multilingual ;
use  DgoraWcas\Product ;
use  DgoraWcas\Helpers ;
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Search
{
    /**
     * Total autocomplete limit
     */
    private  $totalLimit ;
    /**
     * Flexible lmits
     * bool
     */
    private  $flexibleLimits = true ;
    /**
     * Show heading in autocomplete
     * bool
     */
    private  $showHeadings = false ;
    /**
     * Autocomplete groups
     * array
     */
    private  $groups = array() ;
    /**
     * Buffer for post IDs uses for search results page
     * @var null
     */
    private  $postsIDsBuffer = null ;
    /**
     * List of fields in which the phrase is searched
     * @var array
     */
    private  $searchIn = array() ;
    public function __construct()
    {
        $this->searchIn = apply_filters( 'dgwt/wcas/native/search_in', array(
            'title',
            'content',
            'excerpt',
            'sku'
        ) );
        add_filter(
            'posts_search',
            array( $this, 'searchFilters' ),
            501,
            2
        );
        add_filter(
            'posts_where',
            array( $this, 'fixWooExcerptSearch' ),
            100,
            2
        );
        add_filter(
            'posts_distinct',
            array( $this, 'searchDistinct' ),
            501,
            2
        );
        add_filter(
            'posts_join',
            array( $this, 'searchFiltersJoin' ),
            501,
            2
        );
        // Search results page
        add_action( 'init', function () {
            
            if ( apply_filters( 'dgwt/wcas/override_search_results_page', true ) ) {
                add_filter( 'pre_get_posts', array( $this, 'overwriteSearchPage' ), 900001 );
                add_filter(
                    'posts_search',
                    array( 'DgoraWcas\\Helpers', 'clearSearchQuery' ),
                    1000,
                    2
                );
                add_filter(
                    'the_posts',
                    array( 'DgoraWcas\\Helpers', 'rollbackSearchPhrase' ),
                    1000,
                    2
                );
                add_filter(
                    'dgwt/wcas/search_page/result_post_ids',
                    array( $this, 'getProductIds' ),
                    10,
                    2
                );
                $this->BasicAuthBypass();
            }
        
        } );
        // Search results ajax action
        
        if ( DGWT_WCAS_WC_AJAX_ENDPOINT ) {
            add_action( 'wc_ajax_' . DGWT_WCAS_SEARCH_ACTION, array( $this, 'getSearchResults' ) );
        } else {
            add_action( 'wp_ajax_nopriv_' . DGWT_WCAS_SEARCH_ACTION, array( $this, 'getSearchResults' ) );
            add_action( 'wp_ajax_' . DGWT_WCAS_SEARCH_ACTION, array( $this, 'getSearchResults' ) );
        }
    
    }
    
    /**
     * Get search results via ajax
     */
    public function getSearchResults()
    {
        global  $woocommerce ;
        $start = microtime( true );
        if ( !defined( 'DGWT_WCAS_AJAX' ) ) {
            define( 'DGWT_WCAS_AJAX', true );
        }
        $this->groups = $this->searchResultsGroups();
        $this->flexibleLimits = apply_filters( 'dgwt/wcas/flexible_limits', true );
        $this->showHeadings = DGWT_WCAS()->settings->getOption( 'show_grouped_results' ) === 'on';
        
        if ( $this->flexibleLimits ) {
            $totalLimit = DGWT_WCAS()->settings->getOption( 'suggestions_limit', 'int', 7 );
            $this->totalLimit = ( $totalLimit === -1 ? $this->calcFreeSlots() : $totalLimit );
        }
        
        $output = array();
        $results = array();
        $keyword = '';
        $remote = false;
        // Compatibile with v1.1.7
        if ( !empty($_REQUEST['dgwt_wcas_keyword']) ) {
            $keyword = sanitize_text_field( $_REQUEST['dgwt_wcas_keyword'] );
        }
        if ( !empty($_REQUEST['s']) ) {
            $keyword = sanitize_text_field( $_REQUEST['s'] );
        }
        
        if ( !empty($_REQUEST['remote']) ) {
            $remote = true;
            $showHeadings = false;
        }
        
        $keyword = apply_filters( 'dgwt/wcas/phrase', $keyword );
        /* SEARCH IN WOO CATEGORIES */
        
        if ( !$remote && array_key_exists( 'product_cat', $this->groups ) ) {
            $limit = ( $this->flexibleLimits ? $this->totalLimit : $this->groups['product_cat']['limit'] );
            $categories = $this->getCategories( $keyword, $limit );
            $this->groups['product_cat']['results'] = $categories;
        }
        
        /* SEARCH IN WOO TAGS */
        
        if ( !$remote && array_key_exists( 'product_tag', $this->groups ) ) {
            $limit = ( $this->flexibleLimits ? $this->totalLimit : $this->groups['product_tag']['limit'] );
            $tags = $this->getTags( $keyword, $limit );
            $this->groups['product_tag']['results'] = $tags;
        }
        
        /* SEARCH IN PRODUCTS */
        
        if ( apply_filters( 'dgwt/wcas/search_in_products', true ) ) {
            $args = array(
                's'                   => $keyword,
                'posts_per_page'      => -1,
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'ignore_sticky_posts' => 1,
                'order'               => 'DESC',
                'suppress_filters'    => false,
            );
            // Backward compatibility WC < 3.0
            
            if ( Helpers::compareWcVersion( '3.0', '<' ) ) {
                $args['meta_query'] = $this->getMetaQuery();
            } else {
                $args['tax_query'] = $this->getTaxQuery();
            }
            
            $args = apply_filters( 'dgwt/wcas/search_query/args', $args );
            $products = get_posts( $args );
            
            if ( !empty($products) ) {
                $orderedProducts = array();
                $i = 0;
                foreach ( $products as $post ) {
                    
                    if ( $remote ) {
                        $orderedProducts[$i] = new \stdClass();
                        $orderedProducts[$i]->ID = $post->ID;
                    } else {
                        $orderedProducts[$i] = $post;
                    }
                    
                    $orderedProducts[$i]->score = Helpers::calcScore( $keyword, $post->post_title );
                    $i++;
                }
                // Sort by relevance
                usort( $orderedProducts, array( 'DgoraWcas\\Helpers', 'cmpSimilarity' ) );
                // Response for remote requests
                
                if ( $remote ) {
                    $output['suggestions'] = $orderedProducts;
                    $output['time'] = number_format(
                        microtime( true ) - $start,
                        2,
                        '.',
                        ''
                    ) . ' sec';
                    echo  json_encode( apply_filters( 'dgwt/wcas/page_search_results/output', $output ) ) ;
                    die;
                }
                
                $relevantProducts = array();
                $productsSlots = ( $this->flexibleLimits ? $this->totalLimit : $this->groups['product']['limit'] );
                foreach ( $orderedProducts as $post ) {
                    $product = new Product( $post );
                    if ( !$product->isCorrect() ) {
                        continue;
                    }
                    $r = array(
                        'post_id' => $product->getID(),
                        'value'   => html_entity_decode( wp_strip_all_tags( $product->getName() ) ),
                        'url'     => $product->getPermalink(),
                        'type'    => 'product',
                    );
                    // Get thumb HTML
                    if ( DGWT_WCAS()->settings->getOption( 'show_product_image' ) === 'on' ) {
                        $r['thumb_html'] = $product->getThumbnail();
                    }
                    // Get price
                    if ( DGWT_WCAS()->settings->getOption( 'show_product_price' ) === 'on' ) {
                        $r['price'] = $product->getPriceHTML();
                    }
                    // Get description
                    
                    if ( DGWT_WCAS()->settings->getOption( 'show_product_desc' ) === 'on' ) {
                        $wordsLimit = 0;
                        if ( DGWT_WCAS()->settings->getOption( 'show_details_box' ) === 'on' ) {
                            $wordsLimit = 15;
                        }
                        $r['desc'] = $product->getDescription( 'suggestions', $wordsLimit );
                    }
                    
                    // Get SKU
                    if ( DGWT_WCAS()->settings->getOption( 'show_product_sku' ) === 'on' ) {
                        $r['sku'] = $product->getSKU();
                    }
                    // Is on sale
                    //					if ( DGWT_WCAS()->settings->getOption( 'show_sale_badge' ) === 'on' ) {
                    //						$r[ 'on_sale' ] = $product->is_on_sale();
                    //					}
                    // Is featured
                    //					if ( DGWT_WCAS()->settings->getOption( 'show_featured_badge' ) === 'on' ) {
                    //						$r[ 'featured' ] = $product->is_featured();
                    //					}
                    $relevantProducts[] = apply_filters( 'dgwt/wcas/search_results/products', $r, $product );
                    $productsSlots--;
                    if ( $productsSlots === 0 ) {
                        break;
                    }
                }
            }
            
            wp_reset_postdata();
        }
        
        /* END SEARCH IN PRODUCTS */
        if ( !empty($relevantProducts) ) {
            $this->groups['product']['results'] = $relevantProducts;
        }
        $total = ( isset( $products ) ? count( $products ) : 0 );
        
        if ( $this->hasResutls() ) {
            if ( $this->flexibleLimits ) {
                $this->applyFlexibleLimits();
            }
            $results = $this->convertGroupsToSuggestions();
            // Show more
            if ( !empty($this->groups['product']['results']) && count( $this->groups['product']['results'] ) < $total ) {
                $results[] = array(
                    'value' => '',
                    'total' => $total,
                    'url'   => add_query_arg( array(
                    's'         => $keyword,
                    'post_type' => 'product',
                    'dgwt_wcas' => '1',
                ), home_url() ),
                    'type'  => 'more_products',
                );
            }
        } else {
            
            if ( $remote ) {
                $results[] = array(
                    'ID' => 0,
                );
            } else {
                $results[] = array(
                    'value' => '',
                    'type'  => 'no-results',
                );
            }
        
        }
        
        $output['suggestions'] = $results;
        $output['total'] = $total;
        $output['time'] = number_format(
            microtime( true ) - $start,
            2,
            '.',
            ''
        ) . ' sec';
        $output['engine'] = 'free';
        $output['v'] = DGWT_WCAS_VERSION;
        echo  json_encode( apply_filters( 'dgwt/wcas/search_results/output', $output ) ) ;
        die;
    }
    
    /**
     * Get meta query
     * For WooCommerce < 3.0
     *
     * return array
     */
    private function getMetaQuery()
    {
        $meta_query = array(
            'relation' => 'AND',
            1          => array(
            'key'     => '_visibility',
            'value'   => array( 'search', 'visible' ),
            'compare' => 'IN',
        ),
            2          => array(
            'relation' => 'OR',
            array(
            'key'     => '_visibility',
            'value'   => array( 'search', 'visible' ),
            'compare' => 'IN',
        ),
        ),
        );
        // Exclude out of stock products from suggestions
        if ( DGWT_WCAS()->settings->getOption( 'exclude_out_of_stock' ) === 'on' ) {
            $meta_query[] = array(
                'key'     => '_stock_status',
                'value'   => 'outofstock',
                'compare' => 'NOT IN',
            );
        }
        return $meta_query;
    }
    
    /**
     * Get tax query
     * For WooCommerce >= 3.0
     *
     * return array
     */
    private function getTaxQuery()
    {
        $product_visibility_term_ids = wc_get_product_visibility_term_ids();
        $tax_query = array(
            'relation' => 'AND',
        );
        $tax_query[] = array(
            'taxonomy' => 'product_visibility',
            'field'    => 'term_taxonomy_id',
            'terms'    => $product_visibility_term_ids['exclude-from-search'],
            'operator' => 'NOT IN',
        );
        // Exclude out of stock products from suggestions
        if ( DGWT_WCAS()->settings->getOption( 'exclude_out_of_stock' ) === 'on' ) {
            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => $product_visibility_term_ids['outofstock'],
                'operator' => 'NOT IN',
            );
        }
        return $tax_query;
    }
    
    /**
     * Search for matching category
     *
     * @param string $keyword
     * @param int $limit
     *
     * @return array
     */
    public function getCategories( $keyword, $limit = 3 )
    {
        $results = array();
        $args = array(
            'taxonomy' => 'product_cat',
        );
        $productCategories = get_terms( 'product_cat', apply_filters( 'dgwt/wcas/search/product_cat/args', $args ) );
        $keywordUnslashed = wp_unslash( $keyword );
        // Compare keyword and term name
        $i = 0;
        foreach ( $productCategories as $cat ) {
            
            if ( $i < $limit ) {
                $catName = html_entity_decode( $cat->name );
                $pos = strpos( mb_strtolower( $catName ), mb_strtolower( $keywordUnslashed ) );
                
                if ( $pos !== false ) {
                    $termLang = Multilingual::getTermLang( $cat->term_id, 'product_cat' );
                    $results[$i] = array(
                        'term_id'     => $cat->term_id,
                        'taxonomy'    => 'product_cat',
                        'value'       => $catName,
                        'url'         => get_term_link( $cat, 'product_cat' ),
                        'breadcrumbs' => Helpers::getTermBreadcrumbs(
                        $cat->term_id,
                        'product_cat',
                        array(),
                        $termLang,
                        array( $cat->term_id )
                    ),
                        'type'        => 'taxonomy',
                    );
                    // Fix: Remove last separator
                    if ( !empty($results[$i]['breadcrumbs']) ) {
                        $results[$i]['breadcrumbs'] = mb_substr( $results[$i]['breadcrumbs'], 0, -3 );
                    }
                    $i++;
                }
            
            }
        
        }
        return $results;
    }
    
    /**
     * Extend research in the Woo tags
     *
     * @param strong $keyword
     * @param int $limit
     *
     * @return array
     */
    public function getTags( $keyword, $limit = 3 )
    {
        $results = array();
        $args = array(
            'taxonomy' => 'product_tag',
        );
        $productTags = get_terms( 'product_tag', apply_filters( 'dgwt/wcas/search/product_tag/args', $args ) );
        $keywordUnslashed = wp_unslash( $keyword );
        // Compare keyword and term name
        $i = 0;
        foreach ( $productTags as $tag ) {
            
            if ( $i < $limit ) {
                $tagName = html_entity_decode( $tag->name );
                $pos = strpos( mb_strtolower( $tagName ), mb_strtolower( $keywordUnslashed ) );
                
                if ( $pos !== false ) {
                    $results[$i] = array(
                        'term_id'  => $tag->term_id,
                        'taxonomy' => 'product_tag',
                        'value'    => $tagName,
                        'url'      => get_term_link( $tag, 'product_tag' ),
                        'parents'  => '',
                        'type'     => 'taxonomy',
                    );
                    $i++;
                }
            
            }
        
        }
        return $results;
    }
    
    /**
     * Search in extra fields
     *
     * @param string $search SQL
     *
     * @return string prepared SQL
     */
    public function searchFilters( $search, $wp_query )
    {
        global  $wpdb ;
        
        if ( empty($search) || is_admin() ) {
            return $search;
            // skip processing - there is no keyword
        }
        
        
        if ( $this->isAjaxSearch() ) {
            $q = $wp_query->query_vars;
            
            if ( $q['post_type'] !== 'product' ) {
                return $search;
                // skip processing
            }
            
            $n = ( !empty($q['exact']) ? '' : '%' );
            $search = $searchand = '';
            if ( !empty($q['search_terms']) ) {
                foreach ( (array) $q['search_terms'] as $term ) {
                    $term = esc_sql( $wpdb->esc_like( $term ) );
                    $search .= "{$searchand} (";
                    // Search in title
                    
                    if ( in_array( 'title', $this->searchIn ) ) {
                        $search .= "({$wpdb->posts}.post_title LIKE '{$n}{$term}{$n}')";
                    } else {
                        $search .= "(0 = 1)";
                    }
                    
                    // Search in content
                    if ( DGWT_WCAS()->settings->getOption( 'search_in_product_content' ) === 'on' && in_array( 'content', $this->searchIn ) ) {
                        $search .= " OR ({$wpdb->posts}.post_content LIKE '{$n}{$term}{$n}')";
                    }
                    // Search in excerpt
                    if ( DGWT_WCAS()->settings->getOption( 'search_in_product_excerpt' ) === 'on' && in_array( 'excerpt', $this->searchIn ) ) {
                        $search .= " OR ({$wpdb->posts}.post_excerpt LIKE '{$n}{$term}{$n}')";
                    }
                    // Search in SKU
                    if ( DGWT_WCAS()->settings->getOption( 'search_in_product_sku' ) === 'on' && in_array( 'sku', $this->searchIn ) ) {
                        $search .= " OR (dgwt_wcasmsku.meta_key='_sku' AND dgwt_wcasmsku.meta_value LIKE '{$n}{$term}{$n}')";
                    }
                    $search .= ")";
                    $searchand = ' AND ';
                }
            }
            
            if ( !empty($search) ) {
                $search = " AND ({$search}) ";
                if ( !is_user_logged_in() ) {
                    $search .= " AND ({$wpdb->posts}.post_password = '') ";
                }
            }
        
        }
        
        return $search;
    }
    
    /**
     * @param $where
     *
     * @return string
     */
    public function searchDistinct( $where )
    {
        if ( $this->isAjaxSearch() ) {
            return 'DISTINCT';
        }
        return $where;
    }
    
    /**
     * Join the postmeta column in the search posts SQL
     */
    public function searchFiltersJoin( $join, $query )
    {
        global  $wpdb ;
        
        if ( empty($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product' ) {
            return $join;
            // skip processing
        }
        
        if ( $this->isAjaxSearch() && !is_admin() ) {
            if ( DGWT_WCAS()->settings->getOption( 'search_in_product_sku' ) === 'on' && in_array( 'sku', $this->searchIn ) ) {
                $join .= " INNER JOIN {$wpdb->postmeta} AS dgwt_wcasmsku ON ( {$wpdb->posts}.ID = dgwt_wcasmsku.post_id )";
            }
        }
        return $join;
    }
    
    /**
     * Corrects the search by excerpt if necessary.
     * WooCommerce adds search in excerpt by defaults and this should be corrected.
     *
     * @param string $where
     *
     * @return string
     * @since 1.1.4
     *
     */
    public function fixWooExcerptSearch( $where )
    {
        global  $wp_the_query ;
        // If this is not a WC Query, do not modify the query
        if ( empty($wp_the_query->query_vars['wc_query']) || empty($wp_the_query->query_vars['s']) ) {
            return $where;
        }
        if ( DGWT_WCAS()->settings->getOption( 'search_in_product_excerpt' ) !== 'on' && in_array( 'excerpt', $this->searchIn ) ) {
            $where = preg_replace( "/OR \\(post_excerpt\\s+LIKE\\s*(\\'\\%[^\\%]+\\%\\')\\)/", "", $where );
        }
        return $where;
    }
    
    /**
     * Disable cache results and narrowing search results to those from our engine
     *
     * @param \WP_Query $query
     */
    public function overwriteSearchPage( $query )
    {
        if ( !Helpers::isSearchQuery( $query ) ) {
            return;
        }
        /**
         * Disable cache: `cache_results` defaults to false but can be enabled
         */
        $query->set( 'cache_results', false );
        if ( !empty($query->query['cache_results']) ) {
            $query->set( 'cache_results', true );
        }
        $query->set( 'dgwt_wcas', $query->query_vars['s'] );
        $phrase = $query->query_vars['s'];
        $orderby = 'post__in';
        $order = 'desc';
        if ( !empty($query->query_vars['orderby']) ) {
            $orderby = ( $query->query_vars['orderby'] === 'relevance' ? 'post__in' : $query->query_vars['orderby'] );
        }
        if ( !empty($query->query_vars['order']) ) {
            $order = strtolower( $query->query_vars['order'] );
        }
        $slugs = strtok( $_SERVER["REQUEST_URI"], '?' );
        if ( $slugs == '/' ) {
            $slugs = '';
        }
        $baseUrl = home_url() . $slugs . \WC_AJAX::get_endpoint( DGWT_WCAS_SEARCH_ACTION );
        $urlPhrase = str_replace( "\\'", "'", $phrase );
        $urlPhrase = str_replace( '\\"', '"', $urlPhrase );
        $args = array(
            's'      => urlencode( $urlPhrase ),
            'remote' => 1,
        );
        if ( Multilingual::isMultilingual() ) {
            $args['l'] = Multilingual::getCurrentLanguage();
        }
        $url = add_query_arg( $args, $baseUrl );
        $postIn = array();
        $correctResponse = false;
        $r = wp_remote_retrieve_body( wp_remote_get( $url, array(
            'timeout' => 120,
        ) ) );
        $decR = json_decode( $r );
        if ( json_last_error() == JSON_ERROR_NONE ) {
            
            if ( is_object( $decR ) && property_exists( $decR, 'suggestions' ) && is_array( $decR->suggestions ) ) {
                $correctResponse = true;
                foreach ( $decR->suggestions as $suggestion ) {
                    $postIn[] = $suggestion->ID;
                }
            }
        
        }
        
        if ( $correctResponse ) {
            // Save for later use
            $this->postsIDsBuffer = $postIn;
            $query->set( 'orderby', $orderby );
            $query->set( 'order', $order );
            $query->set( 'post__in', $postIn );
            // Resetting the key 's' to disable the default search logic.
            $query->set( 's', '' );
        }
    
    }
    
    /**
     * Check if is ajax search processing
     *
     * @return bool
     * @since 1.1.3
     *
     */
    public function isAjaxSearch()
    {
        if ( defined( 'DGWT_WCAS_AJAX' ) && DGWT_WCAS_AJAX ) {
            return true;
        }
        return false;
    }
    
    /**
     * Headline output structure
     *
     * @return array
     */
    public function headlineBody( $headline )
    {
        return array(
            'value' => $headline,
            'type'  => 'headline',
        );
    }
    
    /**
     * Check if the query retuns resutls
     *
     * @return bool
     */
    public function hasResutls()
    {
        $hasResutls = false;
        foreach ( $this->groups as $group ) {
            
            if ( !empty($group['results']) ) {
                $hasResutls = true;
                break;
            }
        
        }
        return $hasResutls;
    }
    
    /**
     * Calc free slots
     *
     * @return int
     */
    public function calcFreeSlots()
    {
        $slots = 0;
        foreach ( $this->groups as $key => $group ) {
            if ( !empty($group['limit']) ) {
                $slots = $slots + absint( $group['limit'] );
            }
        }
        return $slots;
    }
    
    /**
     * Apply flexible limits
     *
     * @return void
     */
    public function applyFlexibleLimits()
    {
        $slots = $this->totalLimit;
        $total = 0;
        $groups = 0;
        foreach ( $this->groups as $key => $group ) {
            
            if ( !empty($this->groups[$key]['results']) ) {
                $total = $total + count( $this->groups[$key]['results'] );
                $groups++;
            }
        
        }
        $toRemove = ( $total >= $slots ? $total - $slots : 0 );
        if ( $toRemove > 0 ) {
            for ( $i = 0 ;  $i < $toRemove ;  $i++ ) {
                $largestGroupCount = 0;
                $largestGroupKey = 'product';
                foreach ( $this->groups as $key => $group ) {
                    
                    if ( !empty($this->groups[$key]['results']) ) {
                        $thisGroupTotal = count( $this->groups[$key]['results'] );
                        
                        if ( $thisGroupTotal > $largestGroupCount ) {
                            $largestGroupCount = $thisGroupTotal;
                            $largestGroupKey = $key;
                        }
                    
                    }
                
                }
                $last = count( $this->groups[$largestGroupKey]['results'] ) - 1;
                if ( isset( $this->groups[$largestGroupKey]['results'][$last] ) ) {
                    unset( $this->groups[$largestGroupKey]['results'][$last] );
                }
            }
        }
    }
    
    /**
     * Prepare suggestions based on groups
     *
     * @return array
     */
    public function convertGroupsToSuggestions()
    {
        $suggestions = array();
        $totalHeadlines = 0;
        foreach ( $this->groups as $key => $group ) {
            
            if ( !empty($group['results']) ) {
                
                if ( $this->showHeadings ) {
                    $suggestions[] = $this->headlineBody( $key );
                    $totalHeadlines++;
                }
                
                foreach ( $group['results'] as $result ) {
                    $suggestions[] = $result;
                }
            }
        
        }
        // Remove products headline when there are only product type suggestion
        
        if ( $totalHeadlines === 1 ) {
            $i = 0;
            $unset = false;
            foreach ( $suggestions as $key => $suggestion ) {
                
                if ( !empty($suggestion['type']) && $suggestion['type'] === 'headline' && $suggestion['value'] === 'product' ) {
                    unset( $suggestions[$i] );
                    $unset = true;
                    break;
                }
                
                $i++;
            }
            if ( $unset ) {
                $suggestions = array_values( $suggestions );
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Order of the search resutls groups
     *
     * @return array
     */
    public function searchResultsGroups()
    {
        $groups = array();
        if ( DGWT_WCAS()->settings->getOption( 'show_matching_categories' ) === 'on' ) {
            $groups['product_cat'] = array(
                'limit' => 3,
            );
        }
        if ( DGWT_WCAS()->settings->getOption( 'show_matching_tags' ) === 'on' ) {
            $groups['product_tag'] = array(
                'limit' => 3,
            );
        }
        $groups['product'] = array(
            'limit' => 7,
        );
        return apply_filters( 'dgwt/wcas/search_groups', $groups );
    }
    
    /**
     * Allow to get the ID of products that have been found
     *
     * @param integer[] $postsIDs
     *
     * @return mixed
     */
    public function getProductIds( $postsIDs )
    {
        if ( $this->postsIDsBuffer !== null ) {
            return $this->postsIDsBuffer;
        }
        return $postsIDs;
    }
    
    /**
     * Basic Auth bypass when retrieving search results from a native engine.
     *
     * @return void
     */
    public function BasicAuthBypass()
    {
        $authorization = Helpers::getBasicAuthHeader();
        if ( $authorization ) {
            add_filter(
                'http_request_args',
                function ( $r, $url ) {
                if ( strpos( $url, \WC_AJAX::get_endpoint( DGWT_WCAS_SEARCH_ACTION ) ) !== false ) {
                    $r['headers']['Authorization'] = Helpers::getBasicAuthHeader();
                }
                return $r;
            },
                10,
                2
            );
        }
    }

}