import React from 'react';
import { render } from 'react-dom';
import { useTable, usePagination, useSortBy, useFilters, useGroupBy, useExpanded } from 'react-table';

document.onreadystatechange = function () {
    if (document.readyState == "complete") {
        main();
    }
}

function Report({ data }) {

    const columns = React.useMemo(() => [
        { Header: 'id', accessor: 'id' },
        { Header: 'text', accessor: 'hastext' },
        { Header: 'title', accessor: 'hastitle' },
        { Header: 'language', accessor: 'haslanguage' },
        { Header: 'tagged', accessor: 'istagged' },
        { Header: 'pages', accessor: 'pagecount' },
        { Header: 'status', accessor: 'status' },
        { Header: 'statustext', accessor: 'statustext' }
    ], []);

    const useTableProps = { data, columns };
    const tableInstance = useTable(useTableProps, useGroupBy, useFilters, useSortBy, useExpanded, usePagination);
    const {
        getTableProps,
        headerGroups,
        getTableBodyProps,
        prepareRow,
        pageOptions,
        page,
        state: { pageIndex, pageSize },
        goToPage,
        previousPage,
        nextPage,
        setPageSize,
        canPreviousPage,
        canNextPage,
        rows
    } = tableInstance;

    return (
        <div className="a11y_check__Report">
            <table {...getTableProps()} className="table">
                <thead>
                    {headerGroups.map(headerGroup => (
                        <tr {...headerGroup.getHeaderGroupProps()}>
                            {headerGroup.headers.map(column => (
                                <th {...column.getHeaderProps()}>{column.render('Header')}</th>
                            ))}
                        </tr>
                    ))}
                </thead>
                <tbody {...getTableBodyProps()}>
                    {page.map((row, i) => {
                        prepareRow(row);
                        return (
                            <tr {...row.getRowProps()}>
                                {row.cells.map(cell => {
                                    return <td {...cell.getCellProps()}>{cell.render('Cell')}</td>
                                })}
                            </tr>
                        )
                    })}
                </tbody>
            </table>

            <div className="a11y_check__Pagination">
                <button onClick={() => gotoPage(0)} disabled={!canPreviousPage}>
                    {'<<'}
                </button>
                <button onClick={() => previousPage()} disabled={!canPreviousPage}>
                    {'<'}
                </button>
                <button onClick={() => nextPage()} disabled={!canNextPage}>
                    {'>'}
                </button>
                <button onClick={() => gotoPage(pageCount - 1)} disabled={!canNextPage}>
                    {'>>'}
                </button>
                <span>
                    Page{' '}
                    <strong>
                        {pageIndex + 1} of {pageOptions.length}
                    </strong>
                </span>
                <span>
                    | Go to page:
                    <input
                        type="number"
                        defaultValue={pageIndex + 1}
                        onChange={e => {
                            const page = e.target.value ? Number(e.target.value) - 1 : 0
                            gotoPage(page)
                        }}
                        style={{ width: '100px' }}
                    />
                </span>
                <select
                    value={pageSize}
                    onChange={e => {
                        setPageSize(Number(e.target.value))
                    }}
                >
                    {[10, 20, 30, 40, 50].map(pageSize => (
                        <option key={pageSize} value={pageSize}>
                            Show {pageSize}
                        </option>
                    ))}
                </select>
            </div>

        </div>
    );
}

function App() {
    return (
        <div className="a11y_check__App">
            <Report data={data} />
        </div>
    );
}

function main() {
    render(<App />, document.querySelector('#a11y_check__Root'));
}