<?php
/* 
Description: Code for a CardzNet Def File for Card Sizes
 
Copyright 2020 Malcolm Shergold

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if (!class_exists('CardzNetCardDefClass'))
{
	class CardzNetCardDefClass // Define class
	{
		// Width and Height of Portrait Cards in px
		const CardWIDTH = 110;
		const CardHEIGHT = 150;

		// Width and Height of 75% Portrait Cards in px
		const CardWIDTH_75pc = 60;
		const CardHEIGHT_75pc = 90;
		
	}

	// Filter to change one and two eyed jacks 
	add_filter('cardznet_filter_oneeyedjack', 'cardznet_load_oneeyedjacks');
	add_filter('cardznet_filter_twoeyedjack', 'cardznet_load_twoeyedjacks');
	
	function cardznet_load_oneeyedjacks($defaultVal)
	{
		return (array('jack-of-hearts', 'jack-of-diamonds'));
	}

	function cardznet_load_twoeyedjacks($defaultVal)
	{
		return (array('jack-of-clubs', 'jack-of-spades'));
	}

}
?>