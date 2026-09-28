<?php
//
// Created on: <27-Jun-2007 18:52:01 dl>
//
// Copyright (C) 1999-2014 eZ Systems AS. All rights reserved.
//
// Copyright (C) 1998 - 2026 7x and others.
//
// This file is part of Exponential. It may be distributed and/or modified
// under the terms of the GNU General Public License v2.0 (or any later
// version) as published by the Free Software Foundation and appearing in the
// file LICENSE included in the packaging of this file.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The GNU General Public License is available at
// https://www.gnu.org/licenses/.
//

function eZSiteINISettings( $parameters )
{
    $installer = new sevenxMultiSiteInstaller( $parameters );

    return $installer->siteINISettings();
}

?>
