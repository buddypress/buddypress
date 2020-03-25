/**
 * WordPress dependencies.
 */
const { Component, Fragment, createElement } = wp.element;
const { Popover } = wp.components;
const { apiFetch } = wp;
const { __ } = wp.i18n;

class AutoCompleter extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			search: '',
			items: [],
			error: '',
		};

		this.searchItemName = this.searchItemName.bind( this );
		this.selectItemName = this.selectItemName.bind( this );
	}

	searchItemName( value ) {
		const { search } = this.state;
		const { component, objectStatus } = this.props;
		this.setState( { search: value } );

		if ( value.length < search.length ) {
			this.setState( { items: [] } );
		}

		let path= '/buddypress/v1/' + component;

		if ( value ) {
			path += '?search=' + encodeURIComponent( value );
		}

		if ( objectStatus ) {
			path += '&status=' + objectStatus;
		}

		apiFetch( { path:  path } ).then( items => {
			this.setState( { items: items } );
		}, error => {
			this.setState( { error: error.message } );
		} );
	}

	selectItemName( event, itemID ) {
		const { onSelectItem } = this.props;
		event.preventDefault();

		this.setState( {
			search: '',
			items: [],
			error: '',
		} );

		return onSelectItem( { itemID: itemID } );
	}

	render() {
		const { search, items } = this.state;
		let { ariaLabel, placeholder, useAvatar } = this.props;
		let itemsList;

		if ( ! ariaLabel ) {
			ariaLabel = __( 'Item\'s name', 'buddypress' );
		}

		if ( ! placeholder ) {
			placeholder = __( 'Enter Item\'s name here…', 'buddypress' );
		}

		if ( items.length ) {
			itemsList = items.map( ( item ) => {
				return (
					<button
						type="button" key={ 'editor-autocompleters__item-item-' + item.id }
						role="option"
						aria-selected="true"
						className="components-button components-autocomplete__result editor-autocompleters__user"
						onClick={ ( event ) => this.selectItemName( event, item.id ) }
					>
						{ useAvatar && (
							<img key="avatar" className="editor-autocompleters__user-avatar" alt="" src={ item.avatar_urls.thumb } />
						) }
						<span key="name" className="editor-autocompleters__user-name">{ item.name }</span>

						{ item.mention_name && (
							<span key="slug" className="editor-autocompleters__user-slug">{ item.mention_name }</span>
						) }
					</button>
				);
			} );
		}

		return (
			<Fragment>
				<input
					type="text"
					value={ search }
					className="components-placeholder__input"
					aria-label={ ariaLabel }
					placeholder={ placeholder }
					onChange={ ( event ) => this.searchItemName( event.target.value ) }
				/>
				{ 0 !== items.length &&
					<Popover
						className="components-autocomplete__popover"
						focusOnMount={ false }
						position="bottom left"
					>
						<div className="components-autocomplete__results">
							{ itemsList }
						</div>
					</Popover>
				}
			</Fragment>
		);
	}
}

export default AutoCompleter;
