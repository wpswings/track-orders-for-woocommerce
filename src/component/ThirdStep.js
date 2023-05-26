import React,{useContext} from 'react';
import Context from '../store/store';
import { Switch, FormGroup, FormControlLabel} from '@material-ui/core';
import { __ } from '@wordpress/i18n';
import { makeStyles } from '@material-ui/core/styles';
const useStyles = makeStyles({
      margin: {
        marginBottom: '20px',
      },
});
const ThirdStep = (props) => {
    const classes = useStyles();
    const ctx = useContext(Context);
    
    return ( 
    <>
    <h3 className="wps-title">{__( 'Setting', 'track-orders-for-woocommerce' ) }</h3>
    <FormGroup>
        <FormControlLabel
            control={
            <Switch
                checked={ctx.formFields['checkedA']}
                onChange={ctx.changeHandler}
                name="checkedA"
                color="primary"
                 />
            }
            label="Enable Plugin"
            className={classes.margin} />
        <FormControlLabel
            control={
            <Switch
                checked={ctx.formFields['checkedB']}
                onChange={ctx.changeHandler}
                name="checkedB"
                color="primary"
               />
            }
            label="Enable track orders Feature"
            className={classes.margin} />
        </FormGroup>
    </>
    )
}
export default ThirdStep;