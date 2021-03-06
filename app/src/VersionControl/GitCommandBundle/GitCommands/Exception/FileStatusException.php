<?php

/*
 * This file is part of the GitCommandBundle package.
 *
 * (c) Paul Schweppe <paulschweppe@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VersionControl\GitCommandBundle\GitCommands\Exception;

use Exception;

/**
 * InvalidDirectoryException.
 *
 * @author Paul Schweppe <paulschweppe@gmail.com>
 */
class FileStatusException extends Exception implements ExceptionInterface
{
}
