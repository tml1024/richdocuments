<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
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
namespace OCA\Richdocuments\Preview;

//.odt, .ott, .oth, .odm, .odg, .otg, .odp, .otp, .ods, .ots, .odc, .odf, .odb, .odi, .oxt
class OpenDocument extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		// \OC::$server->getLogger()->debug('=== OpenDocument::getThumbnail: ' . $path);

		$fileInfo = $fileview->getFileInfo($path);
		if (!$fileInfo) {
			// \OC::$server->getLogger()->debug('... could not get file info');
			return false;
		}

		$useTempFile = $fileInfo->isEncrypted() || !$fileInfo->getStorage()->isLocal();
		if ($useTempFile) {
			$fileName = $fileview->toTmpFile($path);
		} else {
			$fileName = $fileview->getLocalFile($path);
		}
		// \OC::$server->getLogger()->debug('... file name: ' . $fileName);

		$zip = new \ZipArchive();
		// \OC::$server->getLogger()->debug('... created ZipArchive');

		$res = $zip->open($fileName);

		if ($res !== TRUE) {
			// \OC::$server->getLogger()->debug('... could not open ' . $fileName);
			return false;
		}
		// \OC::$server->getLogger()->debug('... opened it');

		$fp = $zip->getStream('Thumbnails/thumbnail.png');

		if (!$fp) {
			// \OC::$server->getLogger()->debug('... no thumbnail? falling back to asking Collabora Online');
			return Office::getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
		}

		$contents = '';
		while (!feof($fp))
			$contents .= fread($fp, 10000);

		fclose($fp);
		// \OC::$server->getLogger()->debug('... read ' . strlen($contents) . ' bytes');

		$image = new \OC_Image();
		$image->loadFromData($contents);
		// \OC::$server->getLogger()->debug('... created and loaded image');

		if ($image->valid()) {
			// \OC::$server->getLogger()->debug('... scaling down');
			$image->scaleDownToFit($maxX, $maxY);

			// \OC::$server->getLogger()->debug('... returning image');
			return $image;
		}

		// \OC::$server->getLogger()->debug('... returning false');
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/vnd.oasis.opendocument.*/';
	}
}
