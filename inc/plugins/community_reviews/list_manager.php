<?php

namespace CommunityReviews;

class ListManager
{
    public $orderColumns;
    public $orderColumnsAliases;
    public $orderDirections;
    public $defaultOrderDirection;
    public $orderExtend;
    public $itemsNum;
    public $perPage;

    public $orderColumn;
    public $orderColumnAlias;
    public $orderDirection;
    public $pagesNum;
    public $page;
    public $limitStart;
    public $limit;

    private $mybb;
    private $baseUrl;
    private $inAcp;

    public function __construct($data, $manualDetect = false)
    {
        $this->mybb = $data['mybb'];
        $this->baseUrl = $data['baseurl'];

        $this->orderColumns = [];
        $this->orderColumnsAliases = [];

        if (!empty($data['order_columns'])) {
            foreach ($data['order_columns'] as $key => $value) {
                if (is_numeric($key)) {
                    $this->orderColumns[] = $value;
                } else {
                    $this->orderColumns[] = $key;
                    $this->orderColumnsAliases[$value] = $key;
                }
            }
        }

        $this->orderDirections = ['asc', 'desc'];

        if (isset($data['order_dir'])) {
            $this->defaultOrderDirection = $data['order_dir'];
        } else {
            $this->defaultOrderDirection = 'asc';
        }

        if (isset($data['order_extend'])) {
            $this->orderExtend = $data['order_extend'];
        }

        $this->itemsNum = (int)$data['items_num'];
        $this->perPage = (int)$data['per_page'];

        $this->inAcp = defined('IN_ADMINCP');

        if (!$manualDetect) {
            $this->detect();
        }
    }

    public function link($column, $title, $appendParameters = true)
    {
        if ($this->orderDirection == 'asc') {
            $linkOrder = 'desc';
            $pointer = '&uarr;';
        } else {
            $linkOrder = 'asc';
            $pointer = '&darr;';
        }

        if ($column == $this->orderColumn || $column == $this->orderColumnAlias) {
            $active = true;
        } else {
            $active = false;
            $pointer = null;
        }

        return '<a href="' . $this->urlWithSortParameters($column, $linkOrder, $appendParameters) . '"' . ($active ? ' class="active"' : null) . '>' . $title . ' ' .  $pointer . '</a>';
    }

    public function pagination()
    {
        if ($this->perPage > 0 && $this->itemsNum > $this->perPage) {
            if ($this->inAcp) {
                return draw_admin_pagination(
                    $this->page,
                    $this->perPage,
                    $this->itemsNum,
                    $this->urlWithSortParameters()
                );
            } else {
                return multipage($this->itemsNum, $this->perPage, $this->page, $this->baseUrl);
            }
        } else {
            return null;
        }
    }

    public function sql()
    {
        return $this->orderSql() . " " . $this->limitSql();
    }

    public function orderSql($orderSyntax = true)
    {
        $sql = null;

        if ($this->orderColumn && $this->orderDirection) {
            $sql .= "`" . $this->orderColumn . "` " . strtoupper($this->orderDirection);

            if ($this->orderExtend) {
                $sql .= ($sql ? ', ' : null) . $this->orderExtend;
            }
        }

        if ($sql && $orderSyntax) {
            $sql = "ORDER BY " . $sql;
        }

        return $sql;
    }

    public function limitSql($limitSyntax = true)
    {
        if ($limitSyntax) {
            return "LIMIT " . $this->limitStart . ", " . $this->limit;
        } else {
            return [
                'limit_start' => $this->limitStart,
                'limit' => $this->limit,
            ];
        }
    }

    public function queryOptions()
    {
        return [
            'order_by' => $this->orderSql(false),
            'limit' => $this->limit,
            'limit_start' => $this->limitStart,
        ];
    }

    public function detect()
    {
        // sorting
        if ($this->orderColumns) {
            if (
                isset($this->mybb->input['sortby']) &&
                in_array($this->mybb->input['sortby'], $this->orderColumns)
            ) {
                if ($aliasedColumn = array_search($this->mybb->input['sortby'], $this->orderColumnsAliases)) {
                    $this->orderColumn = $aliasedColumn;
                    $this->orderColumnAlias = $this->mybb->input['sortby'];
                } else {
                    $this->orderColumn = $this->mybb->input['sortby'];
                    $this->orderColumnAlias = false;
                }
            } else {
                $this->orderColumn = $this->orderColumns[0];
            }
        }

        if (
            isset($this->mybb->input['order']) &&
            in_array($this->mybb->input['order'], $this->orderDirections)
        ) {
            $this->orderDirection = $this->mybb->input['order'];
        } else {
            $this->orderDirection = $this->defaultOrderDirection;
        }

        // pagination
        if ($this->itemsNum < 0) {
            $this->itemsNum = 0;
        }

        if ($this->perPage < 1) {
            $this->pagesNum = 0;
        } else {
            $this->pagesNum = ceil( $this->itemsNum / $this->perPage );
        }

        if (!$this->page) {
            if (
                isset($this->mybb->input['page']) &&
                (int)$this->mybb->input['page'] > 0 &&
                (int)$this->mybb->input['page'] <= $this->pagesNum
            ) {
                $this->page = (int)$this->mybb->input['page'];
            } else {
                $this->page = 1;
            }
        }

        $this->limitStart = ($this->page - 1) * $this->perPage;
        $this->limit = $this->perPage;
    }

    public function urlWithSortParameters($column = false, $linkOrder = false, $appendParameters = true)
    {
        if ($column === false) {
            $column = $this->orderColumn;
        }

        if ($linkOrder === false) {
            $linkOrder = $this->orderDirection;
        }

        return $this->baseUrl . ($appendParameters ? '?' : '&') . 'sortby=' . $column . '&order=' . $linkOrder;
    }

}
