<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ping extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

	public static function pull() {
		log::add('ping','debug','Cron start');
		foreach (self::byType('ping') as $eqLogic) {
			$eqLogic->ping();
		}
		log::add('ping','debug','Cron stop');
	}

	public function preInsert()
	{
		$this->setConfiguration('ip', '127.0.0.1');
		$this->setConfiguration('port', 'http');
		$this->setConfiguration('mode', 'Tcp');
	}

	public function preUpdate()
	{
		switch ($this->getConfiguration('mode')) {
			case "Tcp":
				if ( ! preg_match("/^[0-9]*$/", $this->getConfiguration('port')) )
				{
					$port = getservbyname (strtolower($this->getConfiguration('port')), 'tcp');
					if ( ! is_int($port) )
					{
						ajax::error(__('Erreur de Port (getservbyname)', __FILE__));
						return false;
					}
				}
			case "Icmp":
				if ( ! preg_match("/^[0-9\.]*$/", $this->getConfiguration('ip')) )
				{
					$ip = gethostbyname($this->getConfiguration('ip'));
					if ( $this->getConfiguration('ip') == gethostbyname($this->getConfiguration('ip')) )
					{
						ajax::error(__('Erreur de Hostname (gethostbyname)', __FILE__));
						return false;
					}
				}
				break;
			case "Arp":
				if ( ! preg_match("/^[0-9a-fA-F][0-9a-fA-F]:[0-9a-fA-F][0-9a-fA-F]:[0-9a-fA-F][0-9a-fA-F]:[0-9a-fA-F][0-9a-fA-F]:[0-9a-fA-F][0-9a-fA-F]:[0-9a-fA-F][0-9a-fA-F]$/", strtoupper($this->getConfiguration('mac'))) )
				{
					ajax::error(__('Erreur d\'adresse mac', __FILE__));
					return false;
				}
				break;
		}
	}

 	public function postUpdate()
	{
		if ( $this->getConfiguration('mode') == '' ) {
			$this->setConfiguration('mode', 'Tcp');
		}
		$cmd = $this->getCmd(null, 'state');
		if ( ! is_object($cmd)) {
			$cmd = new pingCmd();
			$cmd->setName('Etat');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('state');
			$cmd->setUnite('');
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setIsHistorized(0);
			$cmd->setEventOnly(1);
			$cmd->save();		
		}
		$cmd = $this->getCmd(null, 'delai');
		if ( ! is_object($cmd)) {
			$cmd = new pingCmd();
			$cmd->setName('Delai');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('delai');
			$cmd->setType('info');
			$cmd->setUnite('ms');
			$cmd->setSubType('numeric');
			$cmd->setIsHistorized(0);
			$cmd->setEventOnly(1);
			$cmd->save();		
		} else {
			$cmd->setUnite('ms');
			$cmd->save();		
		}
		$cmd = $this->getCmd(null, 'ping');
		if ( ! is_object($cmd) ) {
			$cmd = new pingCmd();
			$cmd->setName('Ping');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setLogicalId('ping');
			$cmd->setEventOnly(1);
			$cmd->save();
		}
	}

	public function postInsert()
	{
		$cmd = $this->getCmd(null, 'state');
		if ( ! is_object($cmd)) {
			$cmd = new pingCmd();
			$cmd->setName('Etat');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('state');
			$cmd->setUnite('');
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setIsHistorized(0);
			$cmd->setEventOnly(1);
			$cmd->save();		
		}
		$cmd = $this->getCmd(null, 'ping');
		if ( ! is_object($cmd) ) {
			$cmd = new pingCmd();
			$cmd->setName('Ping');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('action');
			$cmd->setSubType('other');
			$cmd->setLogicalId('ping');
			$cmd->setEventOnly(1);
			$cmd->save();
		}
		$cmd = $this->getCmd(null, 'delai');
		if ( ! is_object($cmd)) {
			$cmd = new pingCmd();
			$cmd->setName('Delai');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('delai');
			$cmd->setUnite('ms');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setIsHistorized(0);
			$cmd->setEventOnly(1);
			$cmd->save();		
		}
	}

	public function ping() {
		if ( $this->getIsEnable() ) {
			log::add('ping','debug','Test '.$this->getHumanName());
			$statuscmd = $this->getCmd(null, 'state');
			$delaicmd = $this->getCmd(null, 'delai');
			
			log::add('ping','debug','mode : '.$this->getConfiguration('mode'));
			switch ($this->getConfiguration('mode')) {
				case "Tcp":
					if ( ! is_int($this->getConfiguration('port')) )
					{
						$port = getservbyname(strtolower($this->getConfiguration('port')), 'tcp');
					}
					else
					{
						$port = $this->getConfiguration('port');
					}
					$ts = microtime(true);
					$socket = @fsockopen(gethostbyname($this->getConfiguration('ip')), $port, $errno, $errstr, 30);
					 
					if( $socket === false ) {
						log::add('ping','debug',"Error ".$errno." => ".$errstr);
						if ($statuscmd->execCmd() != 0) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(0);
						}
					} else {
						$delaicmd->setCollectDate('');
						log::add('ping','debug','Ok in '.((microtime(true) - $ts ) / 1000));
						$delaicmd->event((microtime(true) - $ts ) / 1000);
						if ($statuscmd->execCmd() != 1) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(1);
						}
						fclose($socket);
					}
					break;
				case "Icmp":
					$lastligne = exec(config::byKey('cmd_ping', 'ping').' '.gethostbyname($this->getConfiguration('ip')).' 2>&1', $return, $code);
					if ( $code == 0 )
					{
						log::add('ping','debug','Code :'.$code.' - Ok');
						if ($statuscmd->execCmd() != 1) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(1);
						}
						if ( preg_match('!rtt min/avg/max/mdev = [0-9\.]*/([0-9\.]*)/[0-9\.]*/[0-9\.]* ms!', $lastligne, $matches) ) {
							$delaicmd->setCollectDate('');
							log::add('ping','debug','Delai : '.$matches[1]);
							$delaicmd->event($matches[1]);
						} else {
							log::add('ping','debug','Delai introuvable : '.$lastligne);
						}
					} else {
						log::add('ping','debug','Code :'.$code.' - Ko');
						if ($statuscmd->execCmd() != 0) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(0);
						}
						if ( preg_match('!rtt min/avg/max/mdev = [0-9\.]*/([0-9\.]*)/[0-9\.]*/[0-9\.]* ms!', $lastligne, $matches) ) {
							$delaicmd->setCollectDate('');
							log::add('ping','debug','Delai : '.$matches[1]);
							$delaicmd->event($matches[1]);
						} else {
							log::add('ping','debug','Delai introuvable : '.$lastligne);
						}
					}
					break;
				case "Arp":
					$lastligne = exec(config::byKey('cmd_arp', 'ping').' '.$this->getConfiguration('mac').' 2>&1', $return, $code);
					log::add('ping','debug','Search '.$this->getConfiguration('mac'));
					log::add('ping','debug','Retour commande '.join("\n", $return));
					if ( preg_match("/\t".strtolower($this->getConfiguration('mac'))."\t/", strtolower(join("\n", $return))) )
					{
						log::add('ping','debug','Ok');
						if ($statuscmd->execCmd() != 1) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(1);
						}
					} else {
						log::add('ping','debug','Ko');
						if ($statuscmd->execCmd() != 0) {
							$statuscmd->setCollectDate('');
							$statuscmd->event(0);
						}
					}
					break;
			}
		}
	}

    public static function GetPingCmd() {
		foreach(array('sudo ping -c2 -q', 'ping -c2 -q') as $cmd)
		{
			log::add('ping','debug','Essai la commande pour ping :'.$cmd);
			unset($return);
			$lastligne = exec($cmd.' 127.0.0.1 2>&1', $return, $code);
			log::add('ping','debug','Code :'.$code);
			log::add('ping','debug','Return :'.join(" | ",$return));
			if ( $code == 0 )
			{
				return $cmd;
			}
		}
		return false;
    }

    public static function GetArpCmd() {
		foreach(array('sudo /usr/bin/arp-scan -l', 'sudo /usr/bin/arp-scan -I bond0 -l', 'sudo /usr/bin/arp-scan -I docker0 -l') as $cmd)
		{
			log::add('ping','debug','Essai la commande pour arp :'.$cmd);
			unset($return);
			$lastligne = exec($cmd.' 2>&1', $return, $code);
			log::add('ping','debug','Code :'.$code);
			log::add('ping','debug','Return :'.join(" | ",$return));
			if ( $code == 0 )
			{
				return $cmd." -g --retry=5 -t 800 -T ";
			}
		}
		return false;
    }
}

class pingCmd extends cmd 
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
    public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
		switch ($this->getLogicalId()) {
			case "ping":
				$eqLogic->pull();
				break;
		}
        return true;
    }
}
?>
