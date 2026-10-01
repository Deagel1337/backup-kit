<?php

namespace Deagel1337\Backup\Kit\Archive\Model;

enum ArchiveEntryType: string
{
    case File = 'file';
    case Directory = 'dir';
    case Symlink = 'symlink';
    case Undefined = 'undefined';
}