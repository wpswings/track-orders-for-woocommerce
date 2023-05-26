import React,{useContext,Fragment} from 'react';
import Context from '../store/store';
import {Radio,RadioGroup, FormControlLabel, FormControl, FormLabel, TextField } from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';
import { __ } from '@wordpress/i18n';
const useStyles = makeStyles({
      margin: {
        marginBottom: '20px',
      },
});
export default function FinalStep(props) {
    const classes = useStyles();
    const ctx = useContext(Context)
    return (
        <Fragment>
            <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            <FormLabel component="legend" className="wpsFormLabel">{ __('Bingo! You are all set to take advantage of your business. Lastly, we urge you to allow us collect some','track-orders-for-woocommerce')} <a href='https://wpswings.com/plugin-usage-tracking/' target="_blank" >{__('information','track-orders-for-woocommerce') }</a> { __( 'in order to improve this plugin and provide better support. If you want, you can dis-allow anytime settings, We never track down your personal data. Promise!', 'track-orders-for-woocommerce') }
                </FormLabel>
            </FormControl>
            <FormControl component="fieldset" fullWidth className="fieldsetWrapper">
            </FormControl>
        </Fragment> 
    );
}