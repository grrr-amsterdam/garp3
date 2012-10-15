<?php
/**
 * Garp_Cli_Command_BuildProject_Strategy_Interface
 * Interface for BuildProject strategies.
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6540 $
 * @package      Garp
 * @subpackage   BuildProject
 * @lastmodified $LastChangedDate: 2012-10-10 10:16:37 +0200 (Wed, 10 Oct 2012) $
 */
interface Garp_Cli_Command_BuildProject_Strategy_Interface {
	/**
 	 * Class constructor
 	 * @param String $projectName
 	 * @param String $repository
 	 * @return Void
 	 */
	public function __construct($projectName, $repository = null);


	/**
 	 * Build all the things
 	 * @return Void
 	 */
	public function build();
}
