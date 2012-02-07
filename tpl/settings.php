<form method="post">
<table>
	<tr valign="top">
	<td align="right" class="caption">Dbstat login:password</td>
	<td><input type="text" name="settings[loginpass]" size="50"/></td>
	<td class="comment">Used at login page.</td>
	</tr>
		
	<tr valign="top">
	<td align="right" class="caption">Statistics name</td>
	<td><input type="text" name="settings[instance]" size="50"/></td>
	<td class="comment">Added to E-mails and page titles.</td>
	</tr>
	
	<tr valign="top">
	<td align="right" class="caption">E-mails to send daily stats</td>
	<td><input type="text" name="settings[emails]" size="50"/></td>
	<td class="comment">Comma-delimited.</td>
	</tr>

	<tr valign="top">
	<td align="right" class="caption">E-mails to send monthly stats</td>
	<td><input type="text" name="settings[emails_month]" size="50"/></td>
	<td class="comment">Comma-delimited.</td>
	</tr>
	
	<tr valign="top">
	<td align="right" class="caption">Reply-To E-mail</td>
	<td><input type="text" name="settings[replyto]" size="50"/></td>
	<td></td>
	</tr>

	<tr valign="top">
	<td align="right" class="caption">Number of columns at the site</td>
	<td><input type="text" name="settings[cols]" size="50"/></td>
	<td></td>
	</tr>

	<tr valign="top">
	<td align="right" class="caption">Number of columns in e-mails</td>
	<td><input type="text" name="settings[cols_email]" size="50"/></td>
	<td></td>
	</tr>
	
	<tr valign="top">
	<td align="right" class="caption">Skip data before YYYY-MM-DD</td>
	<td><input type="text" name="settings[mindate]" size="50"/></td>
	<td class="comment">Will not display data earlier than this date.</td>
	</tr>
	
	<tr valign="top">
	<td align="right" class="caption">API access keys</td>
	<td><input type="text" name="settings[apikeys]" size="50"/></td>
	<td class="comment">Used to restrict access to API; space-delimited.</td>
	</tr>

	<tr valign="top">
	<td align="right" class="caption">Go to this tag after login</td>
	<td><input type="text" name="settings[tagafterlogin]" size="50"/></td>
	<td class="comment">If set, only items tagged with such tag are shown after login.</td>
	</tr>

	<tr valign="top">
	<td><br/></td>
	<td><input type="submit" name="doSave" value="Save settings"/></td>
	<td></td>
	</tr>
</table>
</form>
