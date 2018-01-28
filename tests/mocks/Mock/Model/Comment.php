<?php
/**
 * Model used by Garp_Model_DbTest.
 *
 * @package Mock_Model
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Mock_Model_Comment extends Garp_Model_Db {
    protected $_primary = 'id';

    protected $_name = 'comments';

    protected $_referenceMap = [
        'Post' => [
            'refTableClass' => 'Mock_Model_Post',
            'columns' => 'post_id',
            'refColumns' => 'id'
        ]
    ];
}
