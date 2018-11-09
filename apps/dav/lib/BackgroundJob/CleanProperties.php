<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCP\IDBConnection;
use OCP\ILogger;

/**
 * Class CleanProperties
 *
 * @package OCA\DAV\BackgroundJob
 */
class CleanProperties extends TimedJob {
	/** @var IDBConnection  */
	private $connection;
	/** @var ILogger  */
	private $logger;

	/**
	 * CleanProperties constructor.
	 *
	 * @param IDBConnection $connection
	 * @param ILogger $logger
	 */
	public function __construct(IDBConnection $connection,
								ILogger $logger) {
		$this->connection = $connection;
		$this->logger = $logger;

		//Run once in a day
		$this->setInterval(24*60*60);
	}

	/**
	 * Delete the orphan fileid from oc_properties table
	 *
	 * @param string $fileid fileid of oc_properties table
	 */
	private function deleteOrphan($fileid) {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('properties')
			->where($qb->expr()->eq('fileid', $qb->expr()->literal($fileid)));
		$qb->execute();
		$this->logger->info("Deleting property for fileid: {$fileid}", ['app' => 'dav']);
	}

	/**
	 * Gathers the fileid which are orphan in the oc_properties table
	 * and then deletes them
	 */
	private function processProperties() {
		$qb = $this->connection->getQueryBuilder();

		/**
		 * select prop.fileid from oc_properties prop
		 * left join oc_filecache fc on fc.fileid = prop.fileid
		 * where fc.fileid is not null
		 */
		$qb->select('prop.fileid')
			->from('properties', 'prop')
			->where($qb->expr()->isNull('fc.fileid'))
			->leftJoin('prop', 'filecache', 'fc', $qb->expr()->eq('prop.fileid', 'fc.fileid'));

		$result = $qb->execute();

		while($row = $result->fetch()) {
			$this->deleteOrphan($row['fileid']);
		}
	}

	protected function run($argument) {
		$this->processProperties();
	}
}
