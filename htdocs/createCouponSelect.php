<?php

class CodeSql
{
	/**
	 * The raw code string given to the class.
	 *
	 * @var string
	 */
	protected $_rawCodes;

	/**
	 * The formated codes.
	 *
	 * @var array
	 */
	protected $_formatedCodes	= array();

	/**
	 * Defaultsetting for codesPerLine.
	 *
	 * @var string
	 */
	protected $_codesPerLine	= 'one';

	/**
	 * Default separator for multiple codes per line.
	 *
	 * @var string
	 */
	protected $_codesPerLineSeparator	= ';';

	/**
	 * Default enclosure.
	 *
	 * @var string
	 */
	protected $_enclosure	= '';

	/**
	 * The position of a code nested within a string. FALSE if not nested.
	 *
	 * @var multiple
	 */
	protected $_nestedPos	= FALSE;

	/**
	 * Default separator for nested codes.
	 *
	 * @var string
	 */
	protected $_nestedSeparator	= ' ';

	/**
	 * Default value for sqlType.
	 *
	 * @var string
	 */
	protected $_sqlType	= 'selectCoupon';

	/**
	 * The id of the item to generate a report for.
	 *
	 * @var integer
	 */
	protected $_itemId	= 0;

	/**
	 * Prepares data needed to create the sql.
	 *
	 * @param array $params The request parameter.
	 */
	public function __construct($params)
	{
		$this->_rawCodes				= $params['rawCodes'];
		$this->_codesPerLine			= $params['codesPerLine'];
		$this->_codesPerLineSeparator	= ($params['codesPerLineSeparator'])	? $params['codesPerLineSeparator']	: $this->_codesPerLineSeparator;
		$this->_enclosure				= ($params['enclosure'])				? $params['enclosure']				: $this->_enclosure;
		$this->_nestedPos				= ($params['nestedPos'])				? $params['nestedPos']				: $this->_nestedPos;
		$this->_nestedSeparator			= ($params['nestedSeparator'])			? $params['nestedSeparator']		: $this->_nestedSeparator;
		$this->_sqlType					= ($params['sqlType'])					? $params['sqlType']				: $this->_sqlType;

		if ('getReportComulated' === $this->_sqlType)
		{
			$this->_itemId	= ($params['itemIdComulated'])	? $params['itemIdComulated']	: $this->_itemId;
		}
		elseif ('getReportDetailed' === $this->_sqlType)
		{
			$this->_itemId	= ($params['itemIdDetailed'])	? $params['itemIdDetailed']	: $this->_itemId;
		}

		$this->_formatedCodes	= $this->_formateCodes($this->_rawCodes);
	}

	/**
	 * Return the codes as tehy were given to the class.
	 *
	 * @return string
	 */
	public function getRawCodes()
	{
		return $this->_rawCodes;
	}

	/**
	 * Returns the value for "codesPerLine" selected in the form.
	 *
	 * @return string
	 */
	public function getCodesPerline(){
		return $this->_codesPerLine;
	}

	/**
	 * Returns the value for "codesPerLineSeparator" given in the form.
	 *
	 * @return string
	 */
	public function getCodesPerlineSeparator(){
		return $this->_codesPerLineSeparator;
	}

	/**
	 * Returns the value for "enclosure" given in the form.
	 *
	 * @return string
	 */
	public function getEnclosure(){
		return $this->_enclosure;
	}

	/**
	 * Returns the value for "nestedPos" given in the form.
	 *
	 * @return string
	 */
	public function getNestedPos(){
		return $this->_nestedPos;
	}

	/**
	 * Returns the value for "nestedPosSeparator" given in the form.
	 *
	 * @return string
	 */
	public function getNestedPosSeparator(){
		return $this->_nestedPosSeparator;
	}

	/**
	 * Returns the value for "sqlType" selected in the form.
	 *
	 * @return string
	 */
	public function getSqlType(){
		return $this->_sqlType;
	}

	/**
	 * Automatically calls the specific method to generate the ordered sql statement.
	 *
	 * @return string
	 */
	public function getSql()
	{
		$methodName	= '_generate' . ucfirst($this->_sqlType) . 'Sql';
		$result		= $this->$methodName();
		return $result;
	}

	/**
	 * Formates the given code string into an array by using the given foration parameters.
	 *
	 * @param string $codes
	 * @return array
	 */
	protected function _formateCodes($codes)
	{
		$codes	= strtoupper($codes);

		// Split the code string into single code parts.
		if ($this->_codesPerLine === 'one') {
			$codes	= explode("\n", $codes);
			$codes	= str_replace("\r", '', $codes);
		}
		elseif ($this->_codesPerLine === 'multiple') {
			$codes = explode($this->_codesPerLineSeparator, $codes);
		}

		// If the code itself ist nested within a string, filter the useless part
		if ($this->_nestedPos && $this->_nestedSeparator)
		{
			foreach ($codes as &$code) {
				$tmp	= explode($this->_nestedSeparator, $code);
				$code	= $tmp[$this->_nestedPos];
			}
		}
		elseif ((false === $this->_nestedPos && $this->_nestedSeparator) || ($this->_nestedPos && '' === $this->_nestedSeparator))
		{
			// error
		}

		// Delete enclosures if existing
		if ($this->_enclosure)
		{
			foreach ($codes as $code) {
				$code	= str_replace($this->_enclosure, '', $code);
			}
		}

		// Enclose codes for sql use
		array_walk($codes, array($this, '_enclose'), '"');

		return $codes;
	}

	/**
	 * Encloses the given item into the given enclosure.
	 *
	 * @param mixed 	$item		The item to enclose.
	 * @param string	$enclosure	The enclosure.
	 */
	protected function _enclose(&$item, $key, $enclosure)
	{
		$item	= $enclosure . $item . $enclosure;
	}

	/**
	 * Generates the sql for simple coupon selection.
	 *
	 * @return string
	 */
	protected function _generateSelectCouponSql(){
		$codes	= $this->_formateCodes($this->_rawCodes);
		$sql	= 	'SELECT' . "\n"
			.	"\t" . '*' . "\n"
			.	'FROM' . "\n"
			.	"\t" . 'coupon' . "\n"
			.	'WHERE' . "\n"
			.	"\t" . 'code IN (' . implode(',' . "\n", $codes) . ');';

		return $sql;
	}


	protected function _generateGetReportComulatedSql()
	{
		$sql	= '
			SELECT
				COUNT(winners.id),
				REPLACE(SUM(winners.bid), ".", ",") AS "Gesamtbetrag Gutscheine"

			FROM
				winners
			LEFT JOIN
				items
			ON
				winners.auction = items.id
			LEFT JOIN
				items_session
		';

		if (0 < count($this->_formatedCodes))
		{
			$sql	.= '
				LEFT JOIN

			';
		}

		$sql	.= '
			ON
				winners.is_id = items_session.is_id

			WHERE
				winners.auction = ' . (int) $this->_itemId . '
			AND
				items_session.is_paid = 1
		';

		if (0 < count($this->_formatedCodes))
		{
			$sql 	.= ' AND ';
		}

		return $sql;
	}
}

$codeSql	= new CodeSql($_POST);

?>

<!DOCTYPE html>
<html>
	<head>
		<title>SQL Generator</title>
	</head>
	<body>
		<div>
			<form action="" method="post">
				<fieldset>
					<legend>Raw code string</legend>
					<textarea rows="10" cols="200" name="rawCodes"><?php echo $codeSql->getRawCodes(); ?></textarea>
				</fieldset>
				<fieldset>
					<legend>How is the given string formated</legend>
					<dl>
						<dt>Codes per line:</dt>
						<dd>
							<input type="radio" name="codesPerLine" id="one" value="one" /> <label for="one">one</label><br />
							<input type="radio" name="codesPerLine" id="multiple" value="multiple" /> <label for="multiple" onclick="getElementById('multiple').focus();getElementById('separator').focus();">multiple separated by</label> <input type="text" name="separator" id="separator" size="1" maxlength="1" />
						</dd>
						<dt>The codes are enclosed by:</dt>
						<dd><input type="enclosure" type="text" size="1" maxlength="1" /></dd>
						<dt>The code ist nested within a string:</dt>
						<dd>At position <input type="text" name="nestedPos" size="1" maxlength="2" /> of an explosion by <input type="text" name="nestedSeparator" size="1" maxlength="1" /></dd>
					</dl>
				</fieldset>
				<fieldset>
					<legend>What would you like to have</legend>
					<dl>
						<dd><input type="radio" name="sqlType" id="selectCoupon" value="selectCoupon" /> <label for="selectCoupon">Select matching coupons</label></dd>
						<dd><input type="radio" name="sqlType" id="validateCodes" value="validateCodes" /> <label for="validateCodes">Select for code validation</label></dd>
						<dd><input type="radio" name="sqlType" id="getReportComulated" value="getReportComulated" /> <label for="getReportComulated">Select for cummulated report for item ID <input type="text" name="itemIdComulated" size="3" /></label></dd>
						<dd><input type="radio" name="sqlType" id="getReportDetailed" value="getReportDetailed" /> <label for="getReportDetailed">Select for detailed report for item ID <input type="text" name="itemIdDetailed" size="3" /></label></dd>
					</dl>
				</fieldset>
				<div>
					<input type="submit" value="Select erstellen" />
				</div>
			</form>
		</div>
		<?php

		$sql	= $codeSql->getSql();
		if (strlen($sql) > 0) {
			echo '<fieldset><legend>Generated SQL</legend><textarea rows="10" cols="200">' . $sql . '</textarea></fieldset>';
		}

		?>
	</body>
</html>