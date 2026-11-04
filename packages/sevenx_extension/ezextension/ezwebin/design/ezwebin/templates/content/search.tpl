{* The search page: the filtered search of the sites (design:content/front_search_page.tpl in the kernel's
   design/standard), the same as the administration's: the search as view parameters, the header form posted and
   answered with a 303, a filter bar (content type, sort order), chips, "Did you mean" and the pages, each result as
   the design's line view. Drawn by the kernel's stylesheets/front_search.css; the design restyles it by setting the
   --exps-* tokens. *}
<div class="border-box">
{include uri='design:content/front_search_page.tpl'}
</div>
