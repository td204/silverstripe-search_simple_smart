<h3><a href="$Link">$MenuTitle</a></h3>
<% if $Excerpt %>
    <p>$Excerpt</p>
<% else_if $Content %>
    <p>$Content.FirstSentence</p>
<% end_if %>
