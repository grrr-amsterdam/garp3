<?php
/**
 * Model used by Garp_Model_DbTest.
 *
 * @package Mock_Model
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Mock_Model_Post extends Garp_Model_Db {
    protected $_primary = 'id';

    protected $_name = 'posts';
}
