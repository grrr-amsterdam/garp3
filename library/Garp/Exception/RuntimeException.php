<?php
/**
 * Base class for specific exception per severity level.
 * Note that this extends ErrorException rather than Garp_Exception since ErrorException feels more
 * designed for runtime errors (taking line, file and severity as parameters).
 *
 * @package Garp_Exception
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Exception_RuntimeException extends ErrorException {

}
