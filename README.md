simpleStatistics
================

Plugin to display cumulative galley views and downloads.

For the numbers to be displayed, there must be a hook placed in article_details.tpl, preferably in the entry_details section.
```
{call_hook name="Templates::Article::Details::SimpleStatistics"}
```
The hook has to be placed either in 
```
./templates/frontend/objects/article_details.tpl
```
or the article_details.tpl of the theme used.


System requirements
--------------------
OJS version 3.2.1 




