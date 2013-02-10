$Content

<div id="{$DataClass}_searchform" class="searchForm">
	$Form
</div>

<a class="historyFeedLink" href="registry-feed/latest/{$DataClass}" title="View imported data history">View imported data history</a>

<div id="{$DataClass}_results" class="resultsContainer">
<% if Results %>
	<table class="results" summary="Search results for $DataClass">
		<thead>
			<tr>
				<% control Columns %>
				<th><a href="$Top.QueryLink&amp;Sort={$Name}&amp;Dir={$Top.Direction}#results">$Title</a></th>
				<% end_control %>
			</tr>
		</thead>
		<tbody>
		<% control Results %>
			<tr class="<% if FirstLast %>$FirstLast <% end_if %>$EvenOdd">
				<% control Columns %>
				<td>$Value</td>
				<% end_control %>
			</tr>
		<% end_control %>
		</tbody>
	</table>

	<div class="resultActions">
		<a class="export" href="$Link(export)?$AllQueryVars" title="Export all results to a CSV spreadsheet file">Export results to CSV</a>
	</div>
<% else %>
	<p class="noResults">No results to show.</p>
<% end_if %>

<% if Results.MoreThanOnePage %>
	<div class="pagination">
		<ul class="pageNumbers">
		<% if Results.NotFirstPage %>
			<li class="prev"><a href="$Results.PrevLink" title="View the previous page">&lt;</a></li>
		<% end_if %>
		<% control Results.PaginationSummary(5) %>
		<% if CurrentBool %>
			<li class="active"><a href="$Link" title="View page number $PageNum">$PageNum</a></li>
		<% else %>
			<% if PageNum %>
			<li><a href="$Link" title="View page number $PageNum">$PageNum</a></li>
			<% else %>
			<li><span class="disabled">...</span></li>
			<% end_if %>
		<% end_if %>
		<% end_control %>
		<% if Results.NotLastPage %>
			<li class="next"><a href="$Results.NextLink" title="View the next page">&gt;</a></li>
		<% end_if %>
		</ul>
	</div>
<% end_if %>
</div>

