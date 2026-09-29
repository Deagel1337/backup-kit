<?php

namespace Deagel1337\Backup\Kit\Archive\Model;

enum ArchiveEntryType: string
{
    case File = 'f';
    case Directory = 'd';
    case Symlink = 'l';
}