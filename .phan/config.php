<?php

return [
  'directory_list' => ['src', 'vendor'],
  'exclude_analysis_directory_list' => ['vendor'],
  'suppress_issue_types' => [
    // https://github.com/phan/phan/issues/1143
    'PhanUnanalyzable',
    // https://github.com/phan/phan/issues/2123
    'PhanTypeInvalidDimOffset'
  ]
];
