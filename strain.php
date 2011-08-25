<?php
/**
 * ���������� ������. ����������� ������ ��������� ����������� ����� ����������.
 * ������ �������� ��������� � ����������� ������. ����� ��������� ������ � ������� ����.
 * @copyright Copyright Yamanov Andrey (tenphi@gmail.com)
 * @link http://tenphi.com
 * @version 0.4
 */

namespace lexpro\tools;

use lexpro\Exception;

class Strain {
	
/**
 * ������������� ������ ���� (��������) $name => $scheme.
 * @var array
 * @access private
 */
	private static $_schemes = array();
	
/**
 * ������ ����������� ����������.
 * @var array
 * @access public
 */
	public static $errors = array();
	
/**
 * ������ ��� ���������.
 * @var array
 * @access public
 */
	public static $data = array();
	
/**
 * ���������� � ���������� ������������ ������. TRUE / FALSE.
 * @var boolean
 * @access public
 */
	public static $valid = false;

	const NOCHANGE = 0;
	const COMPLETE = 1;
	const TRUNCATE = 2;
	const SANITIZE = 3;
	
/**
 * ������ ����������.
 * @param mixed $data ������ ��� ����������.
 * @param mixed $filter ��� ������� ��� ����� ����������
 * @param integer $force ����:
 * 		self::NOCHANGE - �� ������ ��������� ������;
 * 		self::COMPLETE - ��������� � ��������� ������ �������� ��������� � ��������� ��������;
 * 		self::TRUNCATE - ������� �� ��������� ������ �������� �� ��������� � ��������� ��������;
 * 		self::SANITIZE - �������� COMPLETE � TRUNCATE ������.
 * @return ������ ����������� ���������� ����������� ��������� �����������
 * 		������.
 * @access private
 */
	private static function _filter(&$data, &$scheme, $force) 
	{
		if (is_callable($scheme)) {
			$return = $scheme($data);
		} else if (is_string($scheme)) {
			if (isset(self::$_schemes[$scheme])) {
				$return = self::_filter($data, self::$_schemes[$scheme], $force);
			} else {
				throw new Exception('Strain scheme `' . $scheme . '` not found.', E_USER_ERROR);
			};
		} else if (is_object($scheme)) {
			// ���� ����������� ������ �� �������� ��������, �� ���������� ���
			// � ������ ������.
			if (!is_object($data)) {
				if ($force == 3) {
					$data = (object) array();
				} else {
					return true;
				}
			}
			// ���������� � ������ ������ ����� ������, ������� ������ ���.
			$return = (object) array();
			if ($force > 1) foreach ($data as $name => &$value) {
				// ���� � ����������� ������� ���� ������� �� ��������� � �������,
				// �� ������� ���.
				if (!isset($scheme->$name)) unset($data->$name);
			}
			foreach ($scheme as $name => &$fil) {
				// ���� � ����������� ������� ��� ������� ���������� � �������,
				// �� ������� ���.
				if (!isset($data->$name)) {
					if ($force % 2 == 1) {
						$data->$name = null;
					} else {
						continue;
					}
				}
				$return->$name = self::_filter($data->$name, $fil, $force);
			}
		} else
		// $scheme - ������/����� ����������
		if (is_array($scheme)) {
			foreach ($scheme as $key => &$sch) {
				if (is_int($key)) {
					if (self::my($data, $sch, $force)->valid) {
						$return = null;
					} else {
						$return = self::$errors;
					}
				} else {
					if (isset(self::$_schemes[$key])) {
						if (is_callable(self::$_schemes[$key])) {
							$filter = self::$_schemes[$key];
							$return = $filter($data, $sch);
						} else {
							$return = self::_filter($data, self::$_schemes[$key], $force);
						}
					} else {
						throw new Exception('Strain scheme `' . $key . '` not found.', E_USER_ERROR);
					}
				}
				if ($return !== null) break;
			}
		}
		return $return;
	}
	
/**
 * ����� ������������ ��������� ������. ��������� ���������� Strain::_filter().
 * @return ������ ���������� ������ ���������� �� ��������� ������.
 * @access public
 */
	public static function my(&$data, &$scheme, $force) 
	{
		self::$data = $data;
		self::$errors = self::_filter($data, &$scheme, $force);
		self::$valid = !self::_bool(self::$errors);
		return (object) array(
			'errors' => self::$errors,
			'data' => $data,
			'valid' => self::$valid
		);
	}
	
/**
 * ����������� ������ ����������� ����������.
 * @var obj ������ ����������� ����������.
 * @return (boolean) - TRUE, ���� ���� ������� ������, FALSE - ���� �� ����.
 */
	private static function _bool($obj) 
	{
		if (is_object($obj)) {
			foreach ($obj as $fld) {
				$res = (is_object($fld) ? (self::_bool($fld) ? true : null) : $fld);
				if ($res !== null) 	return true;
			}
			return false;
		}
		return ($obj !== null ? true : false);
	}
	
/**
 * ������� ��� ������ Strain::my() � ���������� $force = 3.
 * @param mixed $data �������������� ������.
 * @param mixed $scheme ����� ����������.
 * @return ������������ ������.
 * @access public
 */
	public static function sanitize(&$data, &$scheme) 
	{
		return self::my(&$data, &$scheme, 3)->data;
	}
	
/**
 * ������� ��� ������ Strain::my() � ���������� $force = 1.
 * @param mixed $data �������������� ������.
 * @param mixed $scheme ����� ����������.
 * @return ������������ ������.
 * @access public
 */
	public static function complete(&$data, &$scheme) 
	{
		return self::my(&$data, &$scheme, 1)->data;
	}
	
/**
 * ������� ��� ������ Strain::my() � ���������� $force = 2.
 * @param mixed $data �������������� ������.
 * @param mixed $scheme ����� ����������.
 * @return ������������ ������.
 * @access public
 */
	public static function truncate(&$data, &$scheme) 
	{
		self::my(&$data, &$scheme, 2)->data;
		return $data;
	}
	
/**
 * ���������� ����� ����������.
 * @var string $name �������� �����.
 * @var mixed $scheme ���� �����.
 * @return boolean TRUE, ���� ����� ���������. FALSE, ���� ����� �� ���������.
 */
	public static function add($name, $scheme) 
	{
		if (is_string($name) && $name && (is_callable($scheme) || is_array($scheme) || is_object($scheme))) {
			self::$_schemes[$name] = $scheme;
			return true;
		} else {
			return false;
		}
	}
	
/**
 * �������� ����� ����������.
 * @param string $name �������� �����.
 * @return boolean TRUE, ���� �������� ������ �������. FALSE, ���� - ���.
 * @access public
 */
	public static function remove($name) 
	{
		if (is_string($name) && isset(self::$_filters[$name])) {
			unset(self::$_schemes[$name]);
			return true;
		}
		return false;
	}
	
}
